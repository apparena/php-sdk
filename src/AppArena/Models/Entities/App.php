<?php

namespace AppArena\Models\Entities;

use DateTime;

/**
 * App object
 */
class App extends AbstractEntity {

    protected $channels;
    protected $expiryDate;
    protected $startDate;
    protected $templateId;
    protected $versionId;

    /**
     * Initialize app related information and try to get the App ID from different environments
     *
     * @param int $id        ID of the entity
     * @param int $versionId Version ID, which has been submitted during App-Manager initialization
     */
    public function __construct( $id = null, $versionId ) {
        $this->type      = 'app';
        $this->versionId = $versionId;

        // If no App ID available, then try to recover it
        if ( ! $id ) {
            $id = $this->recoverAppId();
        }

        parent::__construct( $id );
    }

    /**
     * @return integer
     */
    public function getTemplateId() {
        return $this->getInfo( 'templateId' );
    }

    /**
     * @return mixed
     */
    public function getExpiryDate(): ?\DateTime {
        $expiryDate = DateTime::createFromFormat('Y-m-d H:i:s', $this->getInfo( 'expiryDate' ));
        return $expiryDate ? $expiryDate : null;
    }

    /**
     * @return mixed
     */
    public function getStartDate(): ?\DateTime {
        $startDate = DateTime::createFromFormat('Y-m-d H:i:s', $this->getInfo( 'startDate' ));
        return $startDate ? $startDate : null;
    }

    /**
     * Returns if the current request contains admin authentication information (GET-params)
     *
     * @param String $projectSecret The project secret to validate the Hash
     *
     * @return bool Returns if the current request contains admin authentication information
     */
    public function isAdmin( $projectSecret ) {
        // Try to get Hash and Timestamp from the request parameters
        if ( isset( $_GET['hash'], $_GET['timestamp'] ) ) {
            $hash      = $_GET['hash'];
            $timestamp = $_GET['timestamp'];
            if ( $hash === sha1( $this->getId() . '_' . $projectSecret . '_' . $timestamp ) && $timestamp >= strtotime( '-1 hours' ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tries to get the App ID from the current environment (e.g. Cookies, Facebook, Request-Parameters)
     *
     * @params array $params Additional information helping to descover the instance ID
     */
    public function recoverAppId() {
        $id = false;

        // Try to get the ID from the REQUEST
        if ( isset( $_REQUEST['appId'] ) ) {
            $id = $_REQUEST['appId'];
        } else {
            if ( isset( $_SERVER['appId'] ) ) {
                $id = $_SERVER['appId'];
            } else {
                // Try to get the ID from the facebook fanpage tab and projectId (app model)
                $id = $this->getIdFromFBRequest();

                if ( ! $id ) {
                    // Try to get the ID from a cookie
                    if ( isset( $_COOKIE['aa_entityId'] ) ) {
                        $id = $_COOKIE['aa_entityId'];
                    } else {
                        // Try to get the ID from the user session
                        if ( ! empty( $_SESSION['current_appId'] ) ) {
                            $id = $_SESSION["current_appId"];
                        }
                    }
                }
            }
        }

        // Set ID to the object and the users session and cookie
        if ( $id ) {
            $_SESSION['current_appId'] = (int) $id;
            $this->id                  = (int) $id;
        }

        return $this->id;
    }


    /**
     * Returns a list of all channels the app is published on in prioritized order (highest channel first)
     * @return array|bool
     */
    public function getChannels() {
        // Return array from Memory if already available
        if ( $this->channels ) {
            return $this->channels;
        }

        // 1. Initialize the default channel (Base Url with direct access)
        $channels = [
            [
                'channelId' => 0,
                'priority'  => 100,
                'type'      => 'domain',
                'name'      => 'Default domain',
                'url'       => $this->getBaseUrl()
            ]
        ];

        // 2. Add a channel which might be added (e.g. added via GET param)
        if ( isset( $_GET['fb_page_id'] ) && $this->getInfo( 'fb_app_id' ) ) {
            // Add channel with high priority as it is explicitly defined in GET parameter
            $channels[] = [
                'channelId' => 0,
                'priority'  => 9999,
                'type'      => 'facebook',
                'name'      => 'Facebook Page added via GET parameter fb_page_id',
                'url'       => 'https://www.facebook.com/' . $_GET['fb_page_id'] . '/app/' . $this->getInfo( 'fb_app_id' ),
            ];
        }

        if ( isset( $_GET['website'] ) ) {
            // Add channel with high priority as it is explicitly defined in GET parameter
            $channels[] = [
                'channelId' => 0,
                'priority'  => 9999,
                'type'      => 'website',
                'name'      => 'Website added via GET parameter website',
                'url'       => $_GET['website'],
            ];
        }
        // App infos is a merged array of basic app information and additional app meta data
        $installedChannels = $this->api->get( 'apps/' . $this->id . '/channels' );

        if ( isset( $installedChannels['_embedded']['data'] ) && is_array( $installedChannels['_embedded']['data'] ) ) {
            $installedChannels = $installedChannels['_embedded']['data'];

            // Prepare data
            $installedChannels = array_map( function ( $channel ) {
                if ( $channel['type'] === 'facebook' ) {
                    // If a target GET parameter is defined and set to 'facebook', then Facebook channels will get higher prio
                    $priority = $channel['priority'] ?? 0;
                    if ( isset( $_GET['target'] ) && $_GET['target'] === 'facebook' ) {
                        $priority = 8888; // Not as high as directly called channels
                    }

                    return [
                        'channelId' => $channel['channelId'],
                        'priority'  => $priority,
                        'type'      => 'facebook',
                        'pageId'    => $channel['value'],
                        'name'      => $channel['name'] ?? 'Channel ID ' . $channel['channelId'],
                        'url'       => 'https://www.facebook.com/' . $channel['value'] . '/app/' . $this->getInfo( 'fb_app_id' ),
                    ];
                }
                if ( $channel['type'] === 'website' ) {
                    // If a target GET parameter is defined and set to 'facebook', then Facebook channels will get higher prio
                    $priority = $channel['priority'] ?? 0;
                    if ( isset( $_GET['target'] ) && $_GET['target'] === 'website' ) {
                        $priority = 8888; // Not as high as directly called channels
                    }

                    return [
                        'channelId' => $channel['channelId'],
                        'priority'  => $priority,
                        'type'      => 'website',
                        'name'      => $channel['name'] ?? 'Channel ID ' . $channel['channelId'],
                        'url'       => $channel['value'],
                    ];
                }
                if ( $channel['type'] === 'domain' ) {
                    // If a target GET parameter is defined and set to 'facebook', then Facebook channels will get higher prio
                    $priority = $channel['priority'] ?? 0;
                    if ( isset( $_GET['target'] ) && $_GET['target'] === 'domain' ) {
                        $priority = 8888; // Not as high as directly called channels
                    }

                    return [
                        'channelId' => $channel['channelId'],
                        'priority'  => $priority,
                        'type'      => 'domain',
                        'name'      => $channel['name'] ?? 'Channel ID ' . $channel['channelId'],
                        'url'       => $channel['value'],
                    ];
                }


            }, $installedChannels );

            // Merge default channels and channels the customer has installed the app on
            $channels = array_merge_recursive( $channels, $installedChannels );
        }

        // Order all channel by priority
        $priority = [];
        foreach ( $channels as $key => $row ) {
            // If the current channel is exoplicitly prioritized via GET param, then give it a high priority
            $channelId = $row['channelId'] ?? 0;
            if ( isset( $_GET['channelId'] ) && $_GET['channelId'] == $channelId ) {
                $row['priority']              = 9999;
                $channels[ $key ]['priority'] = 9999;
            }

            if ( ! isset( $row['priority'] ) ) {
                $row['priority'] = 0;
            }

            $priority[ $key ] = $row['priority'];
        }
        array_multisort( $priority, SORT_DESC, $channels );
        $this->channels = $channels;

        return $this->channels;
    }

    /**
     * @deprecated
     * Returns and sets the instance_id by requesting the API for data
     */
    private function getIdFromFBRequest() {
        $app_data   = [];
        $fb_page_id = false;
        $appId      = false;

        if ( isset( $_REQUEST['signed_request'] ) ) {
            list( $encoded_sig, $payload ) = explode( '.', $_REQUEST['signed_request'], 2 );
            $signed_request = json_decode( base64_decode( strtr( $payload, '-_', '+/' ) ), true );
            if ( isset( $signed_request['app_data'] ) ) {
                $app_data = json_decode( $signed_request['app_data'], true );
            }

            if ( isset( $signed_request['page']['id'] ) && $signed_request['page']['id'] ) {
                $fb_page_id = $signed_request['page']['id'];
            }

            if ( $fb_page_id && $this->versionId ) {
                $request_url = "https://manager.app-arena.com/api/v1/env/fb/pages/" . $fb_page_id .
                    "/instances.json?projectId=" . $this->versionId . "&active=true";
                // If the facebook App ID is submitted, then it will be added to the request
                if ( isset( $_GET['fb_app_id'] ) && strlen( $_GET['fb_app_id'] ) > 10 ) {
                    $request_url .= '&fb_app_id=' . $_GET['fb_app_id'];
                }

                $instances = json_decode( file_get_contents( $request_url ), true );
                foreach ( $instances['data'] as $instance ) {
                    if ( $instance['activate'] == 1 ) {
                        $appId = $instance['i_id'];
                    }
                }
            }
        }

        return $appId;
    }

}
