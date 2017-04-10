<?php

namespace AppArena;

use AppArena\Models\AbstractEntity;
use AppArena\Models\Api;
use AppArena\Models\App;
use AppArena\Models\Cache;
use AppArena\Models\SmartLink;
use AppArena\Models\Template;
use AppArena\Models\Version;

class AppManager {

	protected $root_path = false; // Absolute root path of the project on the server
	private   $cookie; // The App-Manager Cookie for the current user
	private   $css_helper; // Css Helper object
	private   $lang      = 'de_DE'; // Language: e.g. de_DE, en_US, en_UK, ...


	/** @var Api */
	private $api;

	/** @var  App */
	private $app;

	/** @var  Cache */
	private $cache;

	/** @var AbstractEntity */
	private $primaryEntity;

	/** @var  SmartLink */
	private $smartLink;

	/** @var  Template */
	private $template;

	/** @var Version */
	private $version;

	/**
	 * Initialize the App-Manager object
	 *
	 * @param array $options ['apikey'] App-Arena Api Key to authenticate against the API
	 *                       ['appId'] App Id
	 *                       ['cache'] Cache options
	 *                       ['path']
	 *                       ['projectId'] Project ID
	 *                       ['root_path'] Sets the Root path to the app, all path references will be relative to this
	 */
	public function __construct( array $options = [] ) {
		try {

			// Get primary Entity to get information for (version, template or app)
			$this->primaryEntity = $this->getPrimaryEntity();

			// Initialize the cache using the primary entity as cache key
			$cacheOptions = isset( $options['cache'] ) ? $options['cache'] : [];
			$this->cache  = new Cache( array_merge_recursive( $cacheOptions, [
				'namespace' => $this->getPrimaryEntity()->getEntityType() . '_' . $this->getPrimaryEntity()->getId()
			] ) );

			// Initialize the API connection and set it to the primary entity
			$apiKey    = isset( $options['apikey'] ) ? $options['apikey'] : null;
			$this->api = new Api( [
				'cache'  => $this->cache,
				'apikey' => $apiKey
			] );
			$this->getPrimaryEntity()->setApi( $this->api );


		} catch ( \Exception $e ) {
			throw $e;
		}


		// Initialize parameters
		/*if ( isset( $options["projectId"] ) ) {
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


		if ( isset( $options['filename'] ) ) {
			$this->setFilename( $options['filename'] );
		}*/


	}

	/**
	 * @inheritdoc
	 */
	public function getConfig( $configKey, $attr = 'value' ) {
		return $this->primaryEntity->getConfig( $configKey, $attr );
	}

	/**
	 * @inheritdoc
	 */
	public function getConfigs() {
		return $this->primaryEntity->getConfigs();
	}

	/**
	 * @inheritdoc
	 */
	public function getId() {
		return $this->primaryEntity->getId();
	}

	/**
	 * @inheritdoc
	 */
	public function getInfo( $infoKey ) {
		return $this->primaryEntity->getInfo( $infoKey );
	}

	/**
	 * @inheritdoc
	 */
	public function getInfos() {
		return $this->primaryEntity->getInfos();
	}

	/**
	 * @inheritdoc
	 */
	public function getLanguages() {
		return $this->primaryEntity->getLanguages();
	}

	/**
	 * @inheritdoc
	 */
	public function getTranslation( $translationKey, array $args = [] ) {
		return $this->primaryEntity->getTranslation( $translationKey, $args );
	}

	/**
	 * @inheritdoc
	 */
	public function getTranslations() {
		return $this->primaryEntity->getTranslations();
	}

	/**
	 * Returns an implementation of the AbstractEntity object, depending on the Query params available for the current
	 * request.
	 *
	 * @return AbstractEntity Entity object to get information for
	 * @throws \InvalidArgumentException Throws an exception, when now Entity ID is available
	 */
	private function getPrimaryEntity() {

		// If a versionId GET or POST param is set, then the primary Entity is the Version object
		if ( isset( $_GET['versionId'] ) || isset( $_POST['versionId'] ) ) {
			if ( ! $this->version ) {
				$versionId     = isset( $_GET['versionId'] ) ? $_GET['versionId'] : false;
				$versionId     = $versionId ? $versionId : $_POST['versionId'];
				$this->version = new Version( $versionId );
			}

			return $this->version;
		}

		// If a templateId GET or POST param is set, then the primary Entity is the Template object
		if ( isset( $_GET['templateId'] ) || isset( $_POST['templateId'] ) ) {
			if ( ! $this->template ) {
				$templateId     = isset( $_GET['templateId'] ) ? $_GET['templateId'] : false;
				$templateId     = $templateId ? $templateId : $_POST['templateId'];
				$this->template = new Template( $templateId );
			}

			return $this->template;
		}

		// Else the app is the primary object
		if ( ! $this->app ) {
			$this->app = new App();
		}

		// If not even an app ID could be instantiated, then throw an exception
		if (!$this->app->getId()) {
			throw new \InvalidArgumentException('No versionId, templateId or appId available. Please submit any of those IDs to establish the App-Manager connection');
		}

		return $this->app;
	}


	/**
	 * @return App
	 */
	public function getApp() {

		if ( ! $this->app ) {
			// Initialize the current App instance if available
			$this->app = new App( $this->appId, $this->api );

			// Initialize the SmartLink object
			$this->smartLink = new SmartLink( $this->app );

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

		if ( $this->getApp() ) {
			return $this->getApp()->getId();
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
		$allowed = [
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
		];
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
		if ( ! $this->css_helper ) {
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
		$compiled_files = [];
		foreach ( $css_config as $file_id => $css_file ) {
			$css_helper->setFileId( $file_id );
			// Reset settings
			$css_helper->setConfigValues( [] );
			$css_helper->setFiles( [] );
			$css_helper->setVariables( [] );
			$css_helper->setReplacements( [] );

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

		if ( ! $this->smartLink ) {
			$this->getApp();
		}

		return $this->smartLink;
	}


}
