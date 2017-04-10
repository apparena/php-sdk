<?php
/**
 * App-Manager (http://app-arena.readthedocs.org/en/latest/)
 *
 * @link      http://app-arena.readthedocs.org/en/latest/ for complete API and developer documentation
 * @copyright App-Arena.com - iConsultants GmbH (http://www.app-arena.com)
 * @license   2015 -
 */

namespace AppArena\Models;

/**
 * Class Instance Instance object
 */
class App extends AbstractEntity {

	protected $expiryDate;
	protected $templateId;
	protected $versionId;

	/**
	 * @inheritdoc
	 */
	public function __construct( $id = null ) {
		$this->type = 'app';

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
					if ( isset( $_COOKIE['aa_appId'] ) ) {
						$id = $_COOKIE['aa_appId'];
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
			$_SESSION['current_appId'] = intval( $id );
			$this->id                  = intval( $id );
		}

		return $this->id;
	}

	/**
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

			if ( $fb_page_id && $this->projectId ) {
				$request_url = "https://manager.app-arena.com/api/v1/env/fb/pages/" . $fb_page_id .
				               "/instances.json?projectId=" . $this->projectId . "&active=true";
				$instances   = json_decode( file_get_contents( $request_url ), true );
				foreach ( $instances['data'] as $instance ) {
					if ( $instance['activate'] == 1 ) {
						$appId = $instance['appId'];
					}
				}
			}
		}

		return $appId;
	}

	/**
	 * @return boolean
	 */
	public function getVersionId() {
		if ( $this->projectId ) {
			return $this->projectId;
		}

		$this->projectId = $this->getInfo( "projectId" );

		return $this->projectId;
	}

}
