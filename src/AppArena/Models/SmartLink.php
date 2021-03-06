<?php

namespace AppArena\Models;

use AppArena\AppManager;
use AppArena\Models\Entities\AbstractEntity;
use AppArena\Models\Environment\AbstractEnvironment;
use AppArena\Models\Environment\Facebook;
use AppArena\Models\Environment\Website;
use Exception;
use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

/**
 * SmartLink class which handles user redirects for the app
 * User: Sebastian Buckpesch (s.buckpesch@iconsultants.eu)
 * Date: 21.05.14
 */
class SmartLink {

	protected $entity; // Instance object
	protected $environment;

	private $base_path;
	private $base_url;
	/** @var  Cache */
	private $cache;
	private $cookie_key; // SmartCookie key
	private $cookie_domain; // Domain to use for the cookie

	/** @var  Facebook */
	private $facebook;
	/** @var Website */
	private $website; // All available information about the website the app is embedded in
	/** @var Environment\Device */
	private $device;
	/** @var Environment\Browser */
	private $browser;

	private $filename         = "smartlink.php";
	private $lang; // Currently selected language
	private $meta             = []; // Meta data which should be rendered to the share HTML document
	private $paramsAdditional = []; // Additional parameters which will be passed through
	private $paramsExpired    = []; // These expired params will not be set to the cookie any more
	private $reasons          = []; // Array of reasons, why the SmartLink refers to a certain environment
	private $target; // If a target is defined, then this will be used as preferred redirect location
	private $url; // SmartLink Url (Url for sharing)
	private $url_long; // SmartLink Url in long form
	private $url_short; // ShartLink Url processed by an url shortener
	private $url_short_array  = [];
	private $url_target; // The url the user will be redirected to

	// Library objects
	private $mustache; // Mustache engine

	/**
	 * Initializes the SmartLink class with visitor, referrer and environment information
	 *
	 * @param AbstractEntity      $entity      Instance object
	 * @param AbstractEnvironment $environment Environment the app is currently running in
	 * @param Cache               $cache       Cache adapter for managing the link shortener
	 *
	 * @throws Exception When no app ID is passed
	 */
	public function __construct( AbstractEntity $entity, Environment $environment, Cache $cache ) {
		// Initialize the base url
		$this->initBaseUrl();

		// Initialize the app information
		$this->environment = $environment;
		$this->entity      = $entity;
		if ( ! $this->entity ) {
			throw( new Exception( 'No app id available' ) );
		}
		$this->cookie_key = AppManager::COOKIE_KEY . $this->getEntity()->getId();

		// Init cache object
		$this->cache = $cache;

		// Initialize Meta data using default values
		$this->setMeta( [
			'title'       => '',
			'desc'        => '',
			'image'       => '',
			'og_type'     => 'website',
			'schema_type' => 'WebApplication',
		] );

		// Initialize the environment information
		$this->facebook = $this->getEnvironment()->getFacebook();
		$this->website  = $this->getEnvironment()->getWebsite();
		$this->browser  = $this->getEnvironment()->getBrowser();
		$this->device   = $this->getEnvironment()->getDevice();

		// Initializes the SmartCookie
		$this->initCookies();

		// Initialize the SmartLink Url
		$this->initUrl();
	}

	/**
	 * Initializes the baseUrl of the current app
	 */
	private function initBaseUrl() {
		// Initialize the base_url
		if ( isset( $_SERVER['SERVER_NAME'] ) ) {
			$base_url = 'http';
			if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) {
				$base_url .= 's';
			}
			$base_url .= '://';
			$base_url .= $_SERVER['SERVER_NAME'];
			if ( substr( $base_url, - 1 ) !== '/' ) {
				$base_url .= '/';
			}
			$this->base_url = $base_url;
		} else if ( $this->entity ) {
			$this->base_url = $this->entity->getInfo( 'base_url' );
		}

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$url             = parse_url( $_SERVER['REQUEST_URI'] );
			$path_parts      = pathinfo( $url['path'] );
			$base_path       = $path_parts['dirname'];
			$this->base_path = $base_path;
		}

		// Initialize the domain and cookie domain
		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			$host                = $_SERVER['HTTP_HOST'];
			$domain              = $this->extract_domain( $host );
			$this->cookie_domain = "." . $domain;
			if ( $domain === 'localhost' ) {
				$this->cookie_domain = null;
			}
		}

	}

	/**
	 * Extracts the domain from a given domain path (incl. Subdomains)
	 *
	 * @param $domain
	 *
	 * @return mixed
	 */
	private function extract_domain( $domain ) {
		if ( preg_match( "/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i", $domain, $matches ) ) {
			return $matches['domain'];
		}

		return $domain;
	}

	/**
	 * @return AbstractEntity
	 */
	public function getEntity() {
		return $this->entity;
	}

	/**
	 * @return Environment
	 */
	public function getEnvironment() {
		return $this->environment;
	}

	/**
	 * Set all relevant information as json object (stringified) in a cookie on the visitors computer
	 */
	private function initCookies() {
		// Set cookie with the current app ID
		setcookie( 'aa_entityId', $this->getEntity()->getId(), time() + 172600, '/', $this->cookie_domain );

		// Iframe Parameter Passthrough
		// 1. Get parameters from Cookie
		$params        = $this->getCookieValue( 'params' );
		$paramsExpired = [];
		if ( is_array( $params ) ) {
			foreach ( $params as $key => $value ) {
				if ( ! isset( $_GET[ $key ] ) ) {
					// 1.1 Write parameters from the cookie to the Request and set them expired after that
					$_GET[ $key ]          = $value;
					$paramsExpired[ $key ] = $value;
				}
			}
		} else {
			$params = [];
		}
		$this->paramsExpired = $paramsExpired;

		$this->addParams( $params );

		// Set the SmartCookie
		$smart_cookie = $this->toArray();
		$this->setCookieValues( $smart_cookie );

	}

	/**
	 * Returns a value from the SmartCookie
	 *
	 * @param String $key to search in the SmartCookie
	 *
	 * @return mixed Value corresponding to the key
	 */
	private function getCookieValue( $key ) {
		if ( isset( $_COOKIE[ $this->cookie_key ] ) ) {
			// Decode cookie value
			$cookie         = $_COOKIE[ $this->cookie_key ];
			$cookie_decoded = json_decode( $cookie, true );
			if ( isset( $cookie_decoded[ $key ] ) ) {
				return $cookie_decoded[ $key ];
			}
		}

		return false;

	}

	/**
	 * This will add parameters to the smartLink. These parameters will be available as GET-Parameters, when a user
	 * clicks on the smartLink. The parameters will be available as GET parameters as well in the facebook page tab
	 *
	 * @param array $params Array of parameters which should be passed through
	 */
	public function addParams( $params ) {
		foreach ( $params as $key => $value ) {
			$this->paramsAdditional[ $key ] = $value;
		}
	}

	/**
	 * Returns the most important smartlink information as array
	 * @return array Most important smartlink information
	 */
	public function toArray() {
		return [
			'browser'       => $this->getEnvironment()->getBrowser()->toArray(),
			'device'        => $this->getEnvironment()->getDevice()->toArray(),
			'facebook'      => $this->getEnvironment()->getFacebook()->toArray(),
			'entityId'      => $this->getEntity()->getId(),
			'params'        => $this->getParams(),
			'paramsExpired' => $this->paramsExpired,
			'lang'          => $this->getEntity()->getLang(),
			'website'       => $this->getEnvironment()->getWebsite()->toArray(),
		];
	}

	/**
	 * Prepare the params.
	 *
	 * @param bool $includeExpired Should the expired Parameters from the last request be included in the response?
	 *
	 * @return array All parameters currently set
	 */
	public function getParams( $includeExpired = false ) {
		$params = array_merge( $_GET, $this->paramsAdditional );

		// Remove expired params
		if ( ! $includeExpired ) {
			foreach ( $this->paramsExpired as $key => $value ) {
				if ( isset( $params[ $key ] ) ) {
					unset( $params[ $key ] );
				}
			}
		}

		return $params;
	}

  /**
   * Sets values to the SmartCookie
   *
   * @param array $values Array of key value pairs which should be added to the Smart-Cookie cookie
   * @param int $expiration Number of seconds until the cookie will expire
   */
	private function setCookieValues( $values, $expiration = 7200 ) {
		$cookie = [];
		if ( isset( $_COOKIE[ $this->cookie_key ] ) ) {
			$cookie = json_decode( $_COOKIE[ $this->cookie_key ], true );
		}

		if ( ! is_array( $cookie ) ) {
			$cookie = [];
		}

		foreach ( $values as $key => $value ) {
			$cookie[ $key ] = $value;
		}

		// Write the cookie to the users cookies
		$cookie_encoded = json_encode( $cookie );

		setcookie( $this->cookie_key, $cookie_encoded, time() + $expiration, '/', $this->cookie_domain );
	}

	/**
	 * Renders the SmartLink Redirect Share Page
	 *
	 * @param bool $debug Show debug information on the page?
	 */
	public function renderSharePage( $debug = false ) {
		if ( ! $this->mustache ) {
			if ( ! defined( 'SMART_LIB_PATH' ) ) {
				define( 'SMART_LIB_PATH', realpath(__DIR__) . '/..' );
			}
			// Initialize mustache
			$loader         = new Mustache_Loader_FilesystemLoader( SMART_LIB_PATH . '/views' );
			$partials       = new Mustache_Loader_FilesystemLoader( SMART_LIB_PATH . '/views/partials' );
			$this->mustache = new Mustache_Engine( [
				'loader'          => $loader,
				'partials_loader' => $partials,
			] );
		}

		// Get image dimensions from sharing image (for performance reasons only do these kind of requests on the
		// SmartLink page
		$meta = $this->getMeta();
		if ( isset( $meta['image'] ) ) {
			if ( extension_loaded( 'gd' ) && function_exists( 'gd_info' ) && $meta['image'] ) {
				[ $width, $height ] = getimagesize( $meta['image'] );
				$this->meta['image_height'] = $height;
				$this->meta['image_width']  = $width;
			}
		}

		$data = [
			'browser'        => $this->getEnvironment()->getBrowser()->toArray(),
			'channels'       => $this->getEntity()->getChannels(),
			'cookies'        => $this->prepareMustacheArray( $_COOKIE ),
			'debug'          => $debug,
			'device'         => [
				'type' => $this->getDevice()->getDeviceType(),
				'os'   => $this->getEnvironment()->getOperationSystem()->getName()
			],
			'appId'          => $this->getEntity()->getId(),
			'info'           => $this->getEntity()->getInfos(),
			'lang'           => $this->getEntity()->getLang(),
			'meta'           => $this->getMeta(),
			'og_meta'        => $this->prepareMustacheArray( $this->meta['og'] ),
			'params'         => $this->prepareMustacheArray( $this->getParams() ),
			'params_expired' => $this->prepareMustacheArray( $this->paramsExpired ),
			'reasons'        => $this->reasons,
			'target'         => $this->getEnvironment()->getPrimaryEnvironment()->getType(),
			'url'            => $this->getUrl(),
			'url_target'     => $this->getUrlTarget()
		];
		echo $this->mustache->render( 'share', $data );
	}

	/**
	 * @return array
	 */
	public function getMeta() {
		return $this->meta;
	}

	/**
	 * Sets the meta information from the app app config values
	 *
	 * meta array
	 *      ['title']           String Config text identifier for the sharing title
	 *      ['desc']            String Config text identifier for the sharing description
	 *      ['image']           String Config image identifier for the sharing image
	 *      ['og_type']         String Open graph type --> see http://ogp.me/#types
	 *      ['schema_type']     String Schema.org type --> see http://schema.org/docs/full.html
	 *
	 * @param array $meta (see above)
	 *
	 * @return array Returns meta data for the page
	 */
	public function setMeta( $meta ) {
		$title       = $desc = $image = '';
		$og_type     = 'website';
		$schema_type = 'WebApplication';

		// Initialize default values, in case values are missing
		if ( isset( $meta['title'] ) ) {
			$title = $meta['title'];
		}
		if ( isset( $meta['desc'] ) ) {
			$desc = $meta['desc'];
		}
		if ( isset( $meta['image'] ) ) {
			$image = $meta['image'];
		}
		if ( isset( $meta['og_type'] ) ) {
			$og_type = $meta['og_type'];
		}
		if ( isset( $meta['schema_type'] ) ) {
			$schema_type = $meta['schema_type'];
		}

		// Get values from the app config values
		if ( isset( $meta['title'] ) && $this->entity->getConfig( $meta['title'] ) ) {
			$title = $this->entity->getConfig( $meta['title'] );
		}
		if ( isset( $meta['desc'] ) && $this->entity->getConfig( $meta['desc'] ) ) {
			$desc = $this->entity->getConfig( $meta['desc'] );
		}
		if ( isset( $meta['image'] ) && $this->entity->getConfig( $meta['image'] ) ) {
			$image = $this->entity->getConfig( $meta['image'] );
		}

		$this->meta = [
			'title'        => $title,
			'desc'         => $desc,
			'image'        => str_replace( "https://", "http://", $image ),
			'image_secure' => str_replace( "http://", "https://", $image ),
			'og_type'      => $og_type,
			'schema_type'  => $schema_type,
			'url'          => $this->getCurrentUrl()
		];

		// Add Open Graph OG meta-data attributes
		$og_meta = [];
		foreach ( $meta as $key => $value ) {
			if ( substr( $key, 0, 3 ) == 'og:' ) {
				$og_meta[ $key ] = $value;
			}
		}
		$this->meta['og'] = $og_meta;

		return $this->meta;
	}

	/**
	 * Prepares an associative array to be rendered in mustache. The format changes from
	 * array( 'key1" => "value1", "key2" => "value2" ) to
	 * array( array("key" => "key1", "value" => "value1"), array("key" => "key2", "value" => "value2") )
	 *
	 * @param $data array Associative array of data
	 *
	 * @return array Mustache compatible array to be rendered as key value pair
	 */
	private function prepareMustacheArray( $data ) {
		$response = [];
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				continue;
			}
			$response[] = [
				'key'   => $key,
				'value' => $value
			];
		}

		return $response;
	}

	/**
	 * @return Environment\Device
	 */
	public function getDevice() {
		return $this->device;
	}

	/**
	 * Returns the SmartLink Url for the current environment
	 *
	 * @params bool $shortenLink Make a Short Link?
	 *
	 * @return mixed
	 */
	public function getUrl( $shortenLink = false ) {

		$this->initUrl();

		if ( $shortenLink ) {
			$this->url = $this->shortenLink( $this->url );
		}

		return $this->url;
	}

	/**
	 * Initialize the Url compatible to the current device
	 *
	 * @return array All available information about the environments
	 */
	private function initUrl() {

		$channels   = $this->getEntity()->getChannels();
		$deviceType = $this->getEnvironment()->getDevice()->getDeviceType();

		// Check if the channel is compatible with the current device
		foreach ( $channels as $channel ) {
			// Facebook page tab cannot be accessed by mobile and desktop devices
			if ( $channel['type'] == 'facebook' && in_array( $deviceType, [ 'mobile', 'tablet' ] ) ) {
				continue;
			}
			$this->setUrl( $channel );

			return;
		}

	}


	/**
	 * Returns an array of all publication channels in their priority Order
	 *
	 * @return Returns an array of channels prioritized by priority
	 */
	private function getChannels() {
		return $this->getEntity()->getChannels();
	}

	/**
	 * @return mixed
	 */
	public function getUrlTarget() {
		return $this->url_target;
	}

	/**
	 * @param mixed $url_target
	 */
	public function setUrlTarget( $url_target ) {
		$this->url_target = $url_target;
	}

	/**
	 * @return mixed
	 */
	public function getTarget() {
		return $this->target;
	}

	/**
	 * @param mixed $target
	 */
	public function setTarget( $target ) {
		$allowed = [ 'website', 'facebook', 'direct' ];

		if ( in_array( $target, $allowed ) ) {
			$this->target = $target;
		}

	}

	/**
	 * Generates the SmartLink from the submitted url
	 *
	 * @param array $targetChannel Optimal target channel for the current environment
	 * @param bool  $shortenLink   Shorten the SmartLink using bit.ly
	 */
	private function setUrl( $targetChannel, $shortenLink = false ) {
		$share_url          = $this->getBaseUrl() . $this->getFilename();
		$target_url         = $targetChannel['url'];
		$target_original    = $target_url;
		$target_environment = $targetChannel['type'];

		$params = [];

		// Add App-Arena Parameters
		$params[ $this->entity->getEntityType() . 'Id' ] = $this->entity->getId();
		$params['lang']                                  = $this->entity->getLang();

		// When the current environment is Facebook, then add the page ID of the current page to the SmartLink
		$facebook = $this->getFacebook();
		if ( $facebook->getSignedRequest() && $facebook->getPageId() ) {
			$params['fb_page_id'] = $facebook->getPageId();
		}

		// Add additional parameters if available in $this->params
		$params = array_merge( $this->paramsAdditional, $params );

		// Generate sharing and target Url
		foreach ( $params as $key => $value ) {
			if ( $value !== '' ) {
				if ( is_array( $value ) ) {
					$value = json_encode( $value );
				}
				if ( is_string( $value ) ) {
					$value = ltrim( $value, '/' );
				}

				// Add parameter to the Smart-Link
				if ( strpos( $share_url, '?' ) === false ) {
					$share_url .= '?' . $key . '=' . urlencode( $value );
				} else {
					$share_url .= '&' . $key . '=' . urlencode( $value );
				}

				// Add parameter to the Target Url if target not Website or Facebook
				if ( $target_environment !== 'website' && $target_environment !== 'facebook' ) {
					if ( strpos( $target_url, '?' ) === false ) {
						$target_url .= '?' . $key . '=' . urlencode( $value );
					} else {
						$target_url .= '&' . $key . '=' . urlencode( $value );
					}
				}
			}
		}

		// Convert parameters for facebook
		if ( $target_environment === 'facebook' && ! in_array( $this->getDevice()->getDeviceType(), [
				"mobile",
				"tablet"
			] ) ) {
			$target_url = $target_original . '?app_data=' . urlencode( json_encode( $params ) );
		}

		// Shorten Link, when the link changed...
		if ( $shortenLink && $this->url_long !== $share_url ) {
			$this->url_long  = $share_url;
			$share_url       = $this->createGoogleShortLink( $share_url );
			$this->url_short = $share_url;
		} else {
			$this->url_long = $share_url;
		}

		$this->url = $share_url;
		$this->setUrlTarget( $target_url );
	}

	/**
	 * @return string
	 */
	public function getBaseUrl() {
		if ( ! $this->base_url ) {
			$this->initBaseUrl();
		}

		return $this->base_url;
	}

	/**
	 * @param string $base_url
	 */
	public function setBaseUrl( $base_url ) {
		if ( substr( $base_url, - 1 ) !== '/' ) {
			$base_url .= '/';
		}
		$this->base_url = $base_url;
	}

	/**
	 * @return string
	 */
	public function getFilename() {
		return $this->filename;
	}

	/**
	 * @param string $filename
	 */
	public function setFilename( $filename ) {
		$this->filename = $filename;
	}

	/**
	 * @return Facebook
	 */
	public function getFacebook() {
		return $this->facebook;
	}

	/**
	 * @param $url
	 *
	 * @return mixed
	 */
	private function createGoogleShortLink( $url ) {
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_POST, true );
		$parameters = "{'longUrl': '' . $url . ''}";
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $parameters );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, [ 'Content-type: application/json' ] );
		$apiKey = 'AIzaSyB90nkbFL6R-eKB47aVY0WLzlcymcssEdI';
		curl_setopt( $curl, CURLOPT_URL, 'https://www.googleapis.com/urlshortener/v1/url?key=' . $apiKey );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		$data = curl_exec( $curl );
		curl_close( $curl );
		$results  = json_decode( $data );
		$shortURL = $results->id;

		return $shortURL;
	}

	/**
	 * Creates a Short Url using App-Arena Url Shortener
	 *
	 * @param String $url Long Url
	 *
	 * @return String Short Url
	 */
	private function shortenLink( $url ) {
		// Get short links from Cache or memory
		$cache_key = 'shortlinks_' . $this->getEntity()->getId();
		if ( count( $this->url_short_array ) > 0 && isset( $this->url_short_array[ $url ] ) ) {
			return $this->url_short_array[ $url ];
		}

		// Try to get Short Links from Cache
		$cache = $this->getCache()->getAdapter();
		$value = $cache->getItem( $cache_key );
		if ( $value->isHit() ) {
			$response = $value->get();
			if ( isset( $response['status'] ) && $response['status'] === 200 ) {
				$response = json_decode( $response['body'], true );
				if ( $response !== false ) {
					return $response;
				}
			}
		}

		// This short-Link has not been found in cache
		$timestamp = time();
		$signature = md5( $timestamp . '2ff4988406' );
		$api_url   = 'http://smartl.ink/yourls-api.php';

		// Init the CURL session
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $api_url );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );            // No header in the result
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); // Return, do not echo result
		curl_setopt( $ch, CURLOPT_POST, 1 );              // This is a POST request
		curl_setopt( $ch,
			CURLOPT_POSTFIELDS,
			[     // Data to POST
			      'url'       => $url,
			      'format'    => 'json',
			      'action'    => 'shorturl',
			      'timestamp' => $timestamp,
			      'signature' => $signature
			] );

		// Fetch and return content
		$data = curl_exec( $ch );
		curl_close( $ch );

		// Do something with the result. Here, we echo the long URL
		$data = json_decode( $data );
		$this->url_short_array[ $url ] = isset($data->shorturl)? $data->shorturl : $url;

		$value->set( $this->url_short_array );
		$cache->save( $value );

		return isset($data->shorturl)? $data->shorturl : $url;
	}

	/**
	 * @return Cache
	 */
	public function getCache() {
		return $this->cache;
	}

	/**
	 * Overwrites all existing parameters
	 *
	 * @param array $params Array of parameters which should be passed through
	 */
	public function setParams( $params ) {
		$this->paramsAdditional = $params;
	}

	/**
	 * Returns the Url of the website, this app is embedded in
	 * @return Website
	 */
	public function getWebsite() {
		return $this->website;
	}

	/**
	 * Sets the website Url the App is embedded in
	 *
	 * @param mixed $website
	 */
	public function setWebsite( $website ) {
		if ( $website ) {
			$this->website = $website;
			$this->addParams( [ 'website' => $this->website ] );
		}
	}

	/**
	 * @return mixed
	 */
	public function getUrlLong() {
		return $this->url_long;
	}

	/**
	 * @return Environment\Browser
	 */
	public function getBrowser() {
		return $this->browser;
	}

	/**
	 * Returns the url of the current script file
	 *
	 * @param bool $removeParams Should all GET parameters be removed?
	 *
	 * @return string Url of the current script
	 */
	private function getCurrentUrl( $removeParams = false ) {
		if ( ! isset( $_SERVER['SERVER_NAME'] ) || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$pageURL = 'http';
		if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ) {
			$pageURL .= 's';
		}
		$pageURL .= '://';
		$pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

		// Remove GET parameters if wanted
		if ( $removeParams ) {
			$pos     = strpos( $pageURL, '?' );
			$pageURL = substr( $pageURL, 0, $pos );
		}

		return $pageURL;
	}

	/**
	 * Decodes Facebooks signed request parameter
	 *
	 * @param $signed_request Facebook Signed Request
	 *
	 * @return array|mixed Returns the decoded signed request
	 */
	private function parse_signed_request( $signed_request ) {
		if ( $signed_request == false ) {
			return [];
		}

		//$signed_request = $_REQUEST['signed_request'];
		[ $encoded_sig, $payload ] = explode( '.', $signed_request, 2 );
		$data = json_decode( base64_decode( strtr( $payload, '-_', '+/' ) ), true );

		return $data;
	}


}
