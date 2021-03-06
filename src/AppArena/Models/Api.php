<?php
namespace AppArena\Models;

/**
 * Class Api App-Arena App-Manager API object responsible for the communication with the App-Manager REST API
 * @package AppManager\API
 */
class Api {

	protected $apikey   = '';
	protected $base_url = 'https://my.app-arena.com/api/v2/';

	/** @var Cache */
	protected $cache;
	private   $lang;

	/**
	 * @param array $params Parameter to control the initialization
	 *
	 * @throws \InvalidArgumentException When no cache object is available
	 */
	public function __construct( $params = [] ) {

		// Initialize Cache object
		if ( ! isset( $params['cache'] ) || ! $params['cache'] instanceof Cache ) {
			throw new \InvalidArgumentException( 'No valid cache object available. Please read the documentation on how to init the cache' );
		}
		$this->cache = $params['cache'];

		// Initialize Authentication
		if ( isset( $params['apikey'] ) ) {
			$this->apikey = $params['apikey'];
		}
	}


	/**
	 * Returns the data of the requested route as array.
	 *
	 * @param string $route  Requested route
	 * @param array  $params Additional paramater for the request
	 *
	 * @return array|bool API response
	 * @throws \Exception Authorization failed exception
	 */
	public function get( $route, $params = [] ) {

		// Try to get request from the cache
		$cache = $this->getCache()->getAdapter();
		if ( $this->lang ) {
			$cache_key = $this->lang . '_' . str_replace( '/', '_', $route ) . '_' . md5( $route . json_encode( $params ) );
		} else {
			$cache_key = str_replace( '/', '_', $route ) . '_' . md5( $route . json_encode( $params ) );
		}
		$value = $cache->getItem( $cache_key );
		if ( $value->isHit() ) {
			$response = $value->get();
		} else {
			$params['lang'] = $this->lang;
			$response       = $this->_get( $route, $params );

			if ( $response['status'] === 200 && $response !== false ) {
				$value->set( $response );
				$cache->save( $value );

				// Set tags (important to do this after saving, to avoid a loop)
				$responseData = [];
				if (isset($response['body'])) {
					$responseData = json_decode($response['body'], true);
				}
				$value->tag( $this->getTags( $route, $responseData ) );
				$cache->save( $value );
			}

			if ( $response['status'] == 401 ) {
				throw new \Exception( 'Unauthorized request. Please use a valid API Key to send API requests.' );
			}
		}

		if ( $response['status'] === 200 ) {
			$response = json_decode( $response['body'], true );
			if ( $response !== false ) {
				return $response;
			}
		}

		return false;
	}


	/**
	 * Run a GET request against the API
	 *
	 * @param       $path
	 * @param array $params
	 *
	 * @return mixed
	 */
	protected function _get( $path, $params = [] ) {
		$url = $this->base_url . $path;

		if ( $params !== false ) {
			$url .= "?" . http_build_query( $params );
		}

		$apikey  = $this->apikey;
		$ch      = curl_init();
		$headers = [
			'Content-Type:application/json'
		];
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
		if ( $apikey ) {
			$headers[] = 'Authorization: ' . $apikey;
		}
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		$out = curl_exec( $ch );

		if ( $out == false ) {
			$error = curl_error( $ch );
		}

		$httpcode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		return [
			'body'   => $out,
			'status' => $httpcode
		];
	}

	/**
	 * Posts the data as array to the requested route.
	 *
	 * @param string $route  Requested route
	 * @param array  $body   Data for the post
	 * @param array  $params Additional paramater for the request
	 *
	 * @return array API response
	 */
	public function post( $route, $body = [], $params = [] ) {
		$url    = $this->base_url . $route;
		$apikey = $this->apikey;

		$ch      = curl_init();
		$headers = [
			'Content-Type: application/json'
		];

		if ( $params != false ) {
			$url .= "?" . http_build_query( $params );
		}

		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
		if ( $apikey ) {
			$headers[] = 'Authorization: ' . $apikey;
		}
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

		// This sets the number of fields to post
		curl_setopt( $ch, CURLOPT_POST, 1 );

		// This is the fields to post in the form of an array.
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $body ) );
		$out = curl_exec( $ch );


		if ( $out == false ) {
			$error = curl_error( $ch );
		}

		$httpcode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		return $out;
	}

	public function put( $route, $body = [], $params = [] ) {
		$url    = $this->base_url . $route;
		$apikey = $this->apikey;

		$ch      = curl_init();
		$headers = [
			'Content-Type: application/json'
		];


		if ( $params != false ) {
			$url .= "?" . http_build_query( $params );
		}

		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
		if ( $apikey ) {
			$headers[] = 'Authorization: ' . $apikey;
		}
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

		// This sets the number of fields to post
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "PUT" );

		// This is the fields to post in the form of an array.
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $body ) );
		$out = curl_exec( $ch );


		if ( $out == false ) {
			$error = curl_error( $ch );
		}

		$httpcode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		return $out;
	}

	/**
	 * Returns the caching object
	 * @return Cache|bool
	 */
	public function getCache() {
		return $this->cache;
	}

	/**
	 * Cleans the cache
	 */
	public function cleanCache( $cache_key ) {
		$this->getCache()->clean( $cache_key );
	}

	/**
	 * @return string
	 */
	public function getLang() {
		return $this->lang;
	}

	/**
	 * @param string $lang
	 */
	public function setLang( $lang ) {
		$this->lang = $lang;
	}

	/**
	 * Returns tags for the current route
	 *
	 * @param String $route The requested route
	 * @param array $response API response of the last request. May contain relevant tag information
	 *
	 * @return array List of tags for the current API request
	 */
	private function getTags( $route, $response ) {
		$tags       = [];
		$routeParts = explode( '/', $route );

		if ( count( $routeParts ) > 1 && in_array( $routeParts[0], [ 'apps', 'templates', 'versions' ] ) ) {
			switch ( $routeParts[0] ) {
				case 'apps':
					// If the request is an app request, then add templateId tag
					$tags = $this->getTagsForApp( $routeParts, $response );
					break;
				case 'templates':
					// if the request is a template request, then add versionId and parent templateId tags
					$tags = $this->getTagsForTemplate( $routeParts, $response );
					break;
				case 'versions':
					$tags = $this->getTagsForVersion( $routeParts, $response );
					break;
			}
		}

		return $tags;
	}

	/**
	 * Add a tags for the currently requested app
	 *
	 * @param array $routeParts
	 * @param array $response API response of the last request. May contain relevant tag information
	 *
	 * @return array List all of tags for the app
	 */
	private function getTagsForApp( $routeParts, $response ) {
		$tags = [ 'app.' . $routeParts[1] ];

		// Get App Infos
		$infos = $response;
		if (!isset($infos['_embedded']['data']['templateId'])) {
			$infos = $this->get( 'apps/' . $routeParts[1] );
		}
		if ( isset( $infos['_embedded']['data']['templateId'] ) ) {
			$tags[] = 'appTemplate.' . $infos['_embedded']['data']['templateId'];
		}

		// Add second level tags if available
		if ( count( $routeParts ) === 2 ) {
			$tags[] = 'app.' . $routeParts[1] . '.infos';
		}
		if ( isset( $routeParts[2] ) && in_array( $routeParts[2], [ 'configs', 'infos', 'translations', 'languages', 'channels' ] )
		) {
			$tags[] = 'app.' . $routeParts[1] . '.' . $routeParts[2];
		}

		return $tags;
	}

	/**
	 * Add a tags for the currently requested template
	 *
	 * @param array $routeParts
	 * @param array $response API response of the last request. May contain relevant tag information
	 *
	 * @return array List all of tags for the template
	 */
	private function getTagsForTemplate( $routeParts, $response ) {
		$tags = [ 'template.' . $routeParts[1] ];

		// Get Template Infos
		$infos = $response;
		if (!isset($infos['_embedded']['data']['templateId'])) {
			$infos = $this->get( 'templates/' . $routeParts[1] );
		}
		if ( isset( $infos['_embedded']['data']['templateId'] ) && $infos['_embedded']['data']['templateId'] != $routeParts[1] ) {
			$tags[] = 'templateTemplate.' . $infos['_embedded']['data']['templateId'];
		}

		// Add second level tags if available
		if ( count( $routeParts ) === 2 ) {
			$tags[] = 'template.' . $routeParts[1] . '.infos';
		}
		if ( isset( $routeParts[2] ) && in_array( $routeParts[2], [ 'configs', 'infos', 'translations', 'languages', 'channels' ] )
		) {
			$tags[] = 'template.' . $routeParts[1] . '.' . $routeParts[2];
		}

		return $tags;
	}

	/**
	 * Add a tags for the currently requested version
	 *
	 * @param array $routeParts
	 * @param array $response API response of the last request. May contain relevant tag information
	 *
	 * @return array List all of tags for the version
	 */
	private function getTagsForVersion( $routeParts, $response ) {
		$tags = [ 'version.' . $routeParts[1] ];

		// Add second level tags if available
		if ( count( $routeParts ) === 2 ) {
			$tags[] = 'version.' . $routeParts[1] . '.infos';
		}
		if ( isset( $routeParts[2] ) && in_array( $routeParts[2], [ 'configs', 'infos', 'translations', 'languages', 'channels' ] )
		) {
			$tags[] = 'version.' . $routeParts[1] . '.' . $routeParts[2];
		}

		return $tags;
	}

}
