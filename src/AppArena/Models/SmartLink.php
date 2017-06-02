<?php

namespace AppArena\Models;

use AppArena\AppManager;
use AppArena\Models\Entities\AbstractEntity;
use AppArena\Models\Environment\AbstractEnvironment;
use AppArena\Models\Environment\Facebook;
use AppArena\Models\Environment\Website;
use Detection\MobileDetect;
use phpbrowscap\Browscap;

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
	 * @throws \Exception When no app ID is passed
	 */
	public function __construct( AbstractEntity $entity, Environment $environment, Cache $cache ) {
		// Initialize the base url
		$this->initBaseUrl();

		// Initialize the app information
		$this->environment = $environment;
		$this->entity      = $entity;
		if ( ! $this->entity ) {
			throw( new \Exception( 'No app id available' ) );
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
	 * @return array
	 */
	public function getMeta() {
		return $this->meta;
	}

	/**
	 * Initializes the baseUrl of the current app
	 */
	private function initBaseUrl() {
		// Initialize the base_url
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

		$url             = parse_url( $_SERVER['REQUEST_URI'] );
		$path_parts      = pathinfo( $url['path'] );
		$base_path       = $path_parts['dirname'];
		$this->base_path = $base_path;

		// Initialize the domain and cookie domain
		$host                = $_SERVER['HTTP_HOST'];
		$domain              = $this->extract_domain( $host );
		$this->cookie_domain = "." . $domain;
		if ( $domain === 'localhost' ) {
			$this->cookie_domain = null;
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
	 * Analyzes all available data and sets the best suited target environment for the user
	 *
	 * @return array All available information about the environments
	 */
	private function initUrl() {
		$url = false;

		// Due to Safari Cookie Blocking policies, redirect Safari Users to the direct page
		/*$browser = $this->getBrowser();
		if ($browser['name'] == 'Safari') {
			$this->reasons[] = 'BROWSER: Safari is used. Change target to direct';
			$this->setTarget('direct');
		}*/

		// 1. If a website is defined, use the website as default environment
		if ( $this->website->getUrl() ) {
			$this->reasons[] = 'ENV: Website is defined';

			// Validate the Website url
			$website_valid = true;
			if ( strpos( $this->website->getUrl(), 'www.facebook.com/' ) !== false || strpos( $this->website->getUrl(),
					'static.sk.facebook.com' ) !== false || strpos( $this->website->getUrl(),
					'.js' ) !== false
			) {
				$this->reasons[] = 'ENV: Website target is not valid, so it cannot be used as target.';
				$website_valid   = false;
			}

			// Check if another target is defined, then add the website as GET param, but do not use it for redirection
			if ( $this->getUrlTarget() && $this->getTarget() !== 'website' ) {
				$this->reasons[] = 'ENV: Website valid, but another target is defined';
				$this->addParams( [ 'website' => $this->website->getUrl() ] );
				$website_valid = false;
			}

			// If Website is valid, then use it
			if ( $website_valid ) {
				$this->setUrl( $this->website->getUrl() );

				return;
			}
		} else {
			$this->reasons[] = 'ENV: No website parameter defined';
		}

		// If there is no website defined, check if the device is tablet or mobile. If so, use direct access
		if ( in_array( $this->getEnvironment()->getDevice()->getDeviceType(), [ 'mobile', 'tablet' ] ) ) {
			$this->reasons[] = 'DEVICE: User is using a ' . $this->getEnvironment()->getDevice()->getDeviceType() . ' device. Direct Access.';
			if ( $this->getBaseUrl() ) {
				$this->setUrl( $this->getBaseUrl() );
			} else {
				$this->setUrl( $this->entity->getInfo( 'base_url' ) );
			}

			return;
		}

		// So here should be only Desktop devices... So check if facebook page tab information are available...
		$this->reasons[] = 'DEVICE: User is using a desktop device.';
		$facebook        = $this->getFacebook();
		if ( $facebook->getPageId() && $facebook->getAppId() ) {
			$this->reasons[] = 'ENV: Facebook environment data available.';

			// Check if another target is defined, then add the website as GET param, but do not use it for redirection
			$facebook_valid = true;
			if ( $this->getUrlTarget() && $this->getTarget() !== 'facebook' ) {
				$this->reasons[] = 'ENV: Facebook environment valid, but another target is defined';
				$facebook_valid  = false;
			}

			// If Facebook Environment is valid and the facebook should be used as target
			if ( $facebook_valid ) {
				$this->setUrl( $facebook->getPageTab() );

				return;
			}
		}

		// If no optimal url is defined yet, then use direct source
		$this->reasons[] = 'DEVICE: No website or facebook defined. Choose environment direct';
		if ( $this->getBaseUrl() ) {
			$this->setUrl( $this->getBaseUrl() );
		} else {
			$this->setUrl( $this->entity->getInfo( 'base_url' ) );
		}

		return;

	}


	/**
	 * Renders the SmartLink Redirect Share Page
	 *
	 * @param bool $debug Show debug information on the page?
	 */
	public function renderSharePage( $debug = false ) {
		if ( ! $this->mustache ) {
			if ( ! defined( 'SMART_LIB_PATH' ) ) {
				define( 'SMART_LIB_PATH', realpath( dirname( __FILE__ ) ) );
			}
			// Initialize mustache
			$loader         = new \Mustache_Loader_FilesystemLoader( SMART_LIB_PATH . '/views' );
			$partials       = new \Mustache_Loader_FilesystemLoader( SMART_LIB_PATH . '/views/partials' );
			$this->mustache = new \Mustache_Engine( [
				'loader'          => $loader,
				'partials_loader' => $partials,
			] );
		}

		// Get image dimensions from sharing image (for performance reasons only do these kind of requests on the
		// SmartLink page
		$meta = $this->getMeta();
		if ( isset( $meta['image'] ) ) {
			if ( extension_loaded( 'gd' ) && function_exists( 'gd_info' ) && $meta['image'] ) {
				list( $width, $height ) = getimagesize( $meta['image'] );
				$this->meta['image_height'] = $height;
				$this->meta['image_width']  = $width;
			}
		}

		$data = [
			'browser'        => $this->getEnvironment()->getBrowser()->toArray(),
			'cookies'        => $this->prepareMustacheArray( $_COOKIE ),
			'debug'          => $debug,
			'device'         => $this->getEnvironment()->getDevice()->toArray(),
			'entityId'       => $this->getEntity()->getId(),
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
	 * Returns the url of the current script file
	 *
	 * @param bool $removeParams Should all GET parameters be removed?
	 *
	 * @return string Url of the current script
	 */
	private function getCurrentUrl( $removeParams = false ) {
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
			if ( $response['status'] === 200 ) {
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

		$this->url_short_array[ $url ] = $data->shorturl;

		$value->set( $this->url_short_array );
		$cache->save( $value );

		return $data->shorturl;
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
		list( $encoded_sig, $payload ) = explode( '.', $signed_request, 2 );
		$data = json_decode( base64_decode( strtr( $payload, '-_', '+/' ) ), true );

		return $data;
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
	 * Overwrites all existing parameters
	 *
	 * @param array $params Array of parameters which should be passed through
	 */
	public function setParams( $params ) {
		$this->paramsAdditional = $params;
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
	 * Sets values to the SmartCookie
	 *
	 * @param array $values     Array of key value pairs which should be added to the Smart-Cookie cookie
	 * @param int   $expiration Number of seconds until the cookie will expire
	 *
	 * @return array Returns the whole updated cookie as array
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

		return false;

	}


	/**
	 * @return Facebook
	 */
	public function getFacebook() {
		return $this->facebook;
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
	 * Returns the Url of the website, this app is embedded in
	 * @return Website
	 */
	public function getWebsite() {
		return $this->website;
	}

	/**
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
	 * @return mixed
	 */
	public function getUrlLong() {
		return $this->url_long;
	}

	/**
	 * Generates the SmartLink from the submitted url
	 *
	 * @param String $target_url  Url to generate the smartlink from
	 * @param bool   $shortenLink Shorten the SmartLink using bit.ly
	 */
	private function setUrl( $target_url, $shortenLink = false ) {
		$share_url       = $this->getBaseUrl() . $this->getFilename();
		$target_original = $target_url;

		$params = [];

		// Add App-Arena Parameters
		$params['entityId'] = $this->entity->getId();
		$params['lang']     = $this->entity->getLang();

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

				// If it is the first parameter, then use '?', else use  '&'
				if ( strpos( $target_url, '?' ) === false ) {
					$target_url .= '?' . $key . '=' . urlencode( $value );
				} else {
					$target_url .= '&' . $key . '=' . urlencode( $value );
				}
				if ( strpos( $share_url, '?' ) === false ) {
					$share_url .= '?' . $key . '=' . urlencode( $value );
				} else {
					$share_url .= '&' . $key . '=' . urlencode( $value );
				}
			}
		}

		if ( $this->getEnvironment()->getPrimaryEnvironment()->getType() === 'facebook' &&
		     !in_array( $this->getDevice()->getDeviceType(), ["mobile","tablet"]
		     )
		) {
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
	 * @return Environment
	 */
	public function getEnvironment() {
		return $this->environment;
	}

	/**
	 * @param mixed $environment
	 */
	/*private function setEnvironment( $environment ) {
		$allowed = [ 'website', 'facebook', 'direct' ];

		if ( in_array( $environment, $allowed ) ) {
			$this->environment = $environment;
		}
	}*/

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
	 * @return AbstractEntity
	 */
	public function getEntity() {
		return $this->entity;
	}

	/**
	 * @return Cache
	 */
	public function getCache() {
		return $this->cache;
	}

	/**
	 * @return Environment\Device
	 */
	public function getDevice() {
		return $this->device;
	}

	/**
	 * @return Environment\Browser
	 */
	public function getBrowser() {
		return $this->browser;
	}



}
