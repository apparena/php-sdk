<?php
namespace AppArena\Models\Entities;

/**
 * App object
 */
class App extends AbstractEntity {

	protected $channels;
	protected $expiryDate;
	protected $templateId;
	protected $versionId;

	/**
	 * Initialize app related information and try to get the App ID from different environments
	 *
	 * @param int $id ID of the entity
	 * @param int $versionId Version ID, which has been submitted during App-Manager initialization
	 */
	public function __construct( $id = null, $versionId ) {
		$this->type = 'app';
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
		return $this->getInfo('templateId');
	}

	/**
	 * @return mixed
	 */
	public function getExpiryDate() {
		return $this->expiryDate;
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
			$_SESSION['current_appId'] = (int)$id;
			$this->id                  = (int)$id;
		}

		return $this->id;
	}


	/**
	 * Returns a list of all channels the app is published on
	 * @return array|bool
	 */
	public function getChannels() {
		// Return array from Memory if already available
		if ( $this->channels ) {
			return $this->channels;
		}

		// App infos is a merged array of basic app information and additional app meta data
		$channels = $this->api->get( 'apps/' . $this->id . '/channels' );

		if ( isset( $channels['_embedded']['data'] ) && is_array( $channels['_embedded']['data'] ) ) {
			$this->channels = $channels['_embedded']['data'];
		} else {
			return false;
		}

		if ( ! $this->channels ) {
			return false;
		}

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
				if (isset($_GET['fb_app_id']) && strlen($_GET['fb_app_id']) > 10) {
					$request_url .= '&fb_app_id=' . $_GET['fb_app_id'];
				}

				$instances   = json_decode( file_get_contents( $request_url ), true );
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
