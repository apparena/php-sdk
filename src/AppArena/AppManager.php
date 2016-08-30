<?php
namespace AppArena;

use AppArena\API\Api;
use AppArena\Helper\Css;
use AppArena\SmartLink\SmartLink;

class AppManager {

	/**
	 * @var Api $api
	 */
	protected $api; // API object
	protected $cache_dir   = false; // E.g. ROOTPATH . /var/cache, When no path is set, then caching will be deactivated
	protected $root_path   = false; // Absolute root path of the project on the server
	protected $filename    = "smartlink.php"; // Absolute root path of the project on the server
	protected $apikey = null;
	private   $cookie; // The App-Manager Cookie for the current user
	private   $css_helper; // Css Helper object
	private   $appId;
	private   $projectId;
	private   $lang        = "de_DE"; // Language: e.g. de_DE, en_US, en_UK, ...
	/**
	 * @var App App object
	 */
	private $app;
	/**
	 * @var \AppArena\SmartLink\SmartLink SmartLink object
	 */
	private $smart_link;
	private $url;

	/**
	 * Initialize the App-Manager object
	 *
	 * @param array $options Parameter for the initialization
	 *                       'projectId' Project Id
	 *                       'appId' App Id
	 *                       'cache' Cache options
	 *                       'root_path' Sets the Root path to the app, all path references will be relative to this
	 *                       path
	 *                       'filename' Filename of the SmartLink-File (default: smartlink.php)
	 *                       'apikey' Api Key
	 */
	function __construct( $options = array() ) {

		// Initialize parameters
		if ( isset( $options["projectId"] ) ) {
			$this->projectId = $options["projectId"];
		}
		if ( isset( $options["appId"] ) ) {
			$this->appId = $options["appId"];
		}

		if ( isset( $options['root_path'] ) ) {
			$this->root_path = $options['root_path'];
		}

		// Initialize Authentication
		if ( isset( $options['apikey'] ) ) {
			$this->apikey = $options['apikey'];
		}

		// Initialize the cache folder and settings
		if ( isset( $options["cache"] ) && isset( $options["cache"]['dir'] ) ) {
			// If the cache_dir already contains the root_path
			$cache_dir = $options["cache"]['dir'];
			if ( strpos( $cache_dir, $this->root_path ) !== false ) {
				$cache_dir = substr( $cache_dir, strlen( $this->root_path ) );
			}
			$this->cache_dir = $this->root_path . $cache_dir;
		}

		if ( isset( $options['filename'] ) ) {
			$this->setFilename( $options['filename'] );
		}

		// Initialize the API connection
		$this->api = new Api(
			array(
				'cache_dir' => $this->cache_dir,
				"apikey"    => $this->apikey
			)
		);
	}

	/**
	 * @return App
	 */
	public function getApp() {

		if (!$this->app) {
			// Initialize the current App instance if available
			$this->app = new App( $this->appId, $this->api );

			// Initialize the SmartLink object
			$this->smart_link = new SmartLink( $this->app );

			// Create CSS Helper object
			$this->css_helper = new Css(
				$this->cache_dir,
				$this->app,
				"de_DE",
				"style",
				$this->root_path
			);
		}

		return $this->app;
	}

	/**
	 * @param integer|null $id
	 *
	 * @return App
	 */
	/*function getApp( $id = null ) {
		if ( $id ) {
			$app_info = $this->api->get( "apps/" . $id )['_embedded']['data'];
			$app      = new App( $id, $this->api );
			$app->setName( $app_info['name'] );
			$app->setTemplateId( $app_info['templateId'] );
			$app->setLang( $app_info['lang'] );
			$app->setExpiryDate( $app_info['expiryDate'] );
			$app->setCompanyId( $app_info['companyId'] );

			return $app;
		} else {
			$this->getApp()->recoverId();

			return $this->getApp();
		}
	}*/

	/**
	 * @returns App
	 */
	public function createApp( $name, $template_id, $lang, $expiryDate = null, $companyId = null ) {
		$app = new App( null, $this->api );
		$app->setName( $name );
		$app->setTemplateId( $template_id );
		$app->setLang( $lang );
		$app->setExpiryDate( $expiryDate );
		$app->setCompanyId( $companyId );

		return $app;
	}

	/**
	 * Returns the SmartLink Url
	 *
	 * @param bool $shortenLink Make the Short Link?
	 *
	 * @return mixed
	 */
	public function getUrl( $shortenLink = false ) {
		return $this->getSmartLink()->getUrl( $shortenLink );
	}

	/**
	 * Returns the Long version of the smartLink
	 * @return mixed
	 */
	public function getUrlLong() {
		return $this->getSmartLink()->getUrlLong();
	}

	/**
	 * Returns the currently used App ID
	 * @return mixed
	 */
	public function getAppId() {
		if ( $this->appId ) {
			return $this->appId;
		}

		if ( $this->app ) {
			return $this->app->getId();
		}

		return false;
	}


	/**
	 * Returns the currently used Language
	 * @return string Language Code (e.g. de_DE, en_US, ...)
	 */
	public function getLang() {
		return $this->getSmartLink()->getLang();
	}

	/**
	 * Sets a new language for the app manager
	 *
	 * @param string $lang Language Code
	 */
	public function setLang( $lang ) {
		$allowed = array(
			'sq_AL',
			'ar_DZ',
			'ar_BH',
			'ar_EG',
			'ar_IQ',
			'ar_JO',
			'ar_KW',
			'ar_LB',
			'ar_LY',
			'ar_MA',
			'ar_OM',
			'ar_QA',
			'ar_SA',
			'ar_SD',
			'ar_SY',
			'ar_TN',
			'ar_AE',
			'ar_YE',
			'be_BY',
			'bg_BG',
			'ca_ES',
			'zh_CN',
			'zh_HK',
			'zh_SG',
			'hr_HR',
			'cs_CZ',
			'da_DK',
			'nl_BE',
			'nl_NL',
			'en_AU',
			'en_CA',
			'en_IN',
			'en_IE',
			'en_MT',
			'en_NZ',
			'en_PH',
			'en_SG',
			'en_ZA',
			'en_GB',
			'en_US',
			'et_EE',
			'fi_FI',
			'fr_BE',
			'fr_CA',
			'fr_FR',
			'fr_LU',
			'fr_CH',
			'de_AT',
			'de_DE',
			'de_LU',
			'de_CH',
			'el_CY',
			'el_GR',
			'iw_IL',
			'hi_IN',
			'hu_HU',
			'is_IS',
			'in_ID',
			'ga_IE',
			'it_IT',
			'it_CH',
			'ja_JP',
			'ja_JP',
			'ko_KR',
			'lv_LV',
			'lt_LT',
			'mk_MK',
			'ms_MY',
			'mt_MT',
			'no_NO',
			'no_NO',
			'pl_PL',
			'pt_BR',
			'pt_PT',
			'ro_RO',
			'ru_RU',
			'sr_BA',
			'sr_ME',
			'sr_CS',
			'sr_RS',
			'sk_SK',
			'sl_SI',
			'es_AR',
			'es_BO',
			'es_CL',
			'es_CO',
			'es_CR',
			'es_DO',
			'es_EC',
			'es_SV',
			'es_GT',
			'es_HN',
			'es_MX',
			'es_NI',
			'es_PA',
			'es_PY',
			'es_PE',
			'es_PR',
			'es_ES',
			'es_US',
			'es_UY',
			'es_VE',
			'sv_SE',
			'th_TH',
			'th_TH',
			'tr_TR',
			'uk_UA',
			'vi_VN'
		);
		if ( in_array( $lang, $allowed ) ) {
			$this->lang = $lang;
			$this->app->setLang( $this->lang );
			$this->getSmartLink()->setLang( $this->lang );
		}

	}

	/**
	 * Returns the project ID of the currently selected app
	 * @return int project ID
	 */
	public function getProjectId() {
		return $this->projectId;
	}

	/**
	 * Renders the complete smartlink.php page
	 *
	 * @param bool $debug Show debug information on the page?
	 */
	public function renderSharePage( $debug = false ) {
		return $this->getSmartLink()->renderSharePage( $debug );
	}

	/**
	 * Sets the meta data for SmartLink Sharing
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
		return $this->getSmartLink()->setMeta( $meta );
	}

	/**
	 * This will add parameters to the smartLink Url. These parameters will be available as GET-Parameters, when a user
	 * clicks on the smartLink. The parameters will be available as GET parameters as well in the facebook page tab
	 * or within an iframe
	 *
	 * @param array $params Array of parameters which should be passed through
	 */
	public function addParams( $params ) {
		$this->getSmartLink()->addParams( $params );
	}

	/**
	 * Resets all params for the SmartLink Url
	 *
	 * @param array $params Array of parameters which should be passed through
	 */
	public function setParams( $params ) {
		$this->getSmartLink()->setParams( $params );
	}

	/**
	 * Returns all parameters of the SmartLink as array
	 * @return array SmartLink Parameters
	 */
	public function getParams() {
		return $this->getSmartLink()->getParams( true );
	}

	/**
	 * Returns user device information
	 */
	public function getDevice() {
		return $this->getSmartLink()->getDevice();
	}

	/**
	 * Returns the device type of the current device 'mobile', 'tablet', 'desktop'
	 */
	public function getDeviceType() {
		return $this->getSmartLink()->getDeviceType();
	}

	/**
	 * Returns the operating system of the current device
	 */
	public function getOperatingSystem() {
		return $this->getSmartLink()->getOperatingSystem();
	}

	/**
	 * Returns all available Facebook information, like currently used fanpage and canvas information
	 */
	public function getFacebookInfo() {
		return $this->getSmartLink()->getFacebook();
	}

	/**
	 * Returns user browser information
	 */
	public function getBrowser() {
		return $this->getSmartLink()->getBrowser();
	}

	/**
	 * Returns the user's browser name
	 */
	public function getBrowserName() {
		return $this->getSmartLink()->getBrowserName();
	}

	/**
	 * Returns the user's browser major version
	 */
	public function getBrowserVersion() {
		return $this->getSmartLink()->getBrowserVersion();
	}

	/**
	 * Returns if the app currently running on a 'website', 'facebook' or 'direct'
	 * 'website' means the app is embedded via iframe to a website
	 * 'facebook' means the app is embedded in a facebook page tab
	 * 'direct' means the app is being accessed directly without iframe embed
	 */
	public function getEnvironment() {
		return $this->getSmartLink()->getEnvironment();
	}

	/**
	 * Returns the BaseUrl your Sharing Url is generated with. By default it will use the currently used domain
	 * @return string Base Url
	 */
	public function getBaseUrl() {
		return $this->getSmartLink()->getBaseUrl();
	}

	/**
	 * Sets a new base url for your sharing links (-->getUrl()).
	 *
	 * @param string $base_url New base url
	 */
	public function setBaseUrl( $base_url ) {
		$this->getSmartLink()->setBaseUrl( $base_url );
	}

	/**
	 * @return mixed
	 */
	public function getCookie() {
		return $this->cookie;
	}

	/**
	 * @param mixed $cookie
	 */
	public function setCookieValue( $cookie ) {
		$this->cookie = $cookie;
	}

	/**
	 * @return mixed
	 */
	private function getApi() {
		return $this->api;
	}

	/**
	 * Cleans the cache
	 */
	public function cleanCache() {
		$this->getApi()->cleanCache( "apps/" . $this->getAppId() );
	}

	/**
	 * Returns the CSS Helper object, which can be used to generate CSS files from Less or config value sources
	 * @return Css Css helper
	 */
	public function getCssHelper() {
		if (!$this->css_helper) {
			$this->getApp();
		}

		return $this->css_helper;
	}

	/**
	 * @param String $file_id Identifier for the file (in most cases defined css-config file)
	 *
	 * @return String returns the CSS filename of the
	 */
	public function getCssFileName( $file_id ) {
		return $this->getCssHelper()->getCacheKey( $file_id );
	}

	/**
	 * Returns an array of compiled css files. You can submit a CSS config array regarding to the
	 * documentation, including CSS, Less, SCSS files. Furthermore you can define tring replacements
	 * and use config variables of the current app.
	 * @see http://app-arena.readthedocs.org/en/latest/sdk/php/030-css.html
	 *
	 * @param $css_config array CSS Configuration array
	 *
	 * @return array Assocative array including all compiled CSS files
	 */
	public function getCSSFiles( $css_config ) {
		$css_helper     = $this->getCssHelper();
		$compiled_files = array();
		foreach ( $css_config as $file_id => $css_file ) {
			$css_helper->setFileId( $file_id );
			// Reset settings
			$css_helper->setConfigValues( array() );
			$css_helper->setFiles( array() );
			$css_helper->setVariables( array() );
			$css_helper->setReplacements( array() );

			if ( isset( $css_file['config_values'] ) ) {
				$css_helper->setConfigValues( $css_file['config_values'] );
			}
			if ( isset( $css_file['files'] ) ) {
				$css_helper->setFiles( $css_file['files'] );
			}
			if ( isset( $css_file['variables'] ) ) {
				$css_helper->setVariables( $css_file['variables'] );
			}
			if ( isset( $css_file['replacements'] ) ) {
				$css_helper->setReplacements( $css_file['replacements'] );
			}

			$compiled_files[] = $css_helper->getCompiledCss();
		}

		return $compiled_files;
	}

	/**
	 * @return string
	 */
	public function getFilename() {
		return $this->filename;
	}

	/**
	 * Sets the filename for the SmartLink (default: smartlink.php)
	 *
	 * @param string $filename
	 */
	public function setFilename( $filename ) {
		$this->filename = $filename;
		$this->getSmartLink()->setFilename( $filename );
	}

	/**
	 * @return SmartLink
	 */
	public function getSmartLink() {

		if (!$this->smart_link) {
			$this->getApp();
		}

		return $this->smart_link;
	}



}
