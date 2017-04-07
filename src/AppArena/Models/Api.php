<?php
/**
 * App-Manager (http://app-arena.readthedocs.org/en/latest/)
 *
 * @link      http://app-arena.readthedocs.org/en/latest/ for complete API and developer documentation
 * @copyright App-Arena.com - iConsultants GmbH (http://www.app-arena.com)
 * @license   -
 */

namespace AppArena\Models;

/**
 * Class Api App-Arena App-Manager API object responsible for the communication with the App-Manager REST API
 * @package AppManager\API
 */
class Api {

	protected $auth_username = '';
	protected $auth_password = '';
	protected $auth_apikey   = '';
	protected $base_url = 'https://my.app-arena.com/api/v2/';

	/** @var Cache|bool */
	protected $cache = false;

	/**
	 * @param array $params Parameter to control the initialization
	 *                      bool 'cache_reset' Reset the cache on initialization?
	 */
	function __construct( $params = [] ) {

		// Initialize Cache object
		if ( isset( $params['cache'] ) ) {
			$this->cache = $params['cache'];
		}

		// Initialize Authentication
		if ( isset( $params['apikey'] ) ) {
			$this->auth_apikey = $params['apikey'];
		}

	}


	/**
	 * Returns the data of the requested route as array.
	 *
	 * @param string $route  Requested route
	 * @param array  $params Additional paramater for the request
	 *
	 * @return array API response
	 */
	function get( $route, $params = [] ) {
		if ( $this->lang ) {
			$cache_key = str_replace( '/', '_', $route ) . "_" . $this->lang . "_" . md5( $route . json_encode( $params ) );
		} else {
			$cache_key = str_replace( '/', '_', $route ) . "_" . md5( $route . json_encode( $params ) );
		}

		if ( ! $this->cache_reset && $this->cache->exists( $cache_key ) ) {
			$response = $this->cache->load( $cache_key );
		} else {
			$params['lang'] = $this->lang;
			$response       = $this->_get( $route, $params );
			if ( $response != false ) {
				$this->cache->save( $cache_key, $response );
			}
		}

		$response = json_decode( $response, true );

		if ( $response == false ) {
			return false;
		}

		if ( isset( $response['status'] ) ) {
			return false;
		}

		return $response;
	}


	protected function _get( $path, $params = [] ) {
		$url = $this->base_url . $path;

		if ( $params != false ) {
			$url .= "?" . http_build_query( $params );
		}

		$username = $this->auth_username;
		$password = $this->auth_password;
		$apikey   = $this->auth_apikey;
		$ch       = curl_init();
		$headers  = [
			'Content-Type:application/json'
		];
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		if ( $apikey ) {
			$headers[] = 'Authorization: ' . $apikey;
		} elseif ( $username && $password ) {
			$headers[] = 'Authorization: Basic ' . base64_encode( "$username:$password" );
		}
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		$out = curl_exec( $ch );

		if ( $out == false ) {
			$error = curl_error( $ch );
		}

		$httpcode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		//var_dump($httpcode);exit();
		curl_close( $ch );

		return $out;
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
		$url      = $this->base_url . $route;
		$username = $this->auth_username;
		$password = $this->auth_password;
		$apikey   = $this->auth_apikey;

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
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		if ( $apikey ) {
			$headers[] = 'Authorization: ' . $apikey;
		} elseif ( $username && $password ) {
			$headers[] = 'Authorization: Basic ' . base64_encode( "$username:$password" );
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
		$url      = $this->base_url . $route;
		$username = $this->auth_username;
		$password = $this->auth_password;
		$apikey   = $this->auth_apikey;

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
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		if ( $apikey ) {
			$headers[] = 'Authorization: ' . $apikey;
		} elseif ( $username && $password ) {
			$headers[] = 'Authorization: Basic ' . base64_encode( "$username:$password" );
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
	 * @param mixed $lang
	 */
	public function setLang( $lang ) {
		$this->lang = $lang;
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


}
