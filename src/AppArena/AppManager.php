<?php

namespace AppArena;

use AppArena\Exceptions\EntityUnknownException;
use AppArena\Models\CssCompiler;
use AppArena\Models\Entities\AbstractEntity;
use AppArena\Models\Api;
use AppArena\Models\Entities\App;
use AppArena\Models\Cache;
use AppArena\Models\Environment;
use AppArena\Models\SmartLink;
use AppArena\Models\Entities\Template;
use AppArena\Models\Entities\Version;

class AppManager {

	const COOKIE_KEY = 'aa_smartlink_'; // The entity ID should be attached to the key

	protected $root_path = false; // Absolute root path of the project on the server
	private   $cookie; // The App-Manager Cookie for the current user
	/** @var  CssCompiler */
	private $cssCompiler; // Css Helper object
	private $lang = 'de_DE'; // Language: e.g. de_DE, en_US, en_UK, ...


	/** @var Api */
	private $api;

	/** @var  App */
	private $app;

	/** @var  Cache */
	private $cache;

	/** @var  Environment */
	private $environment;

	/** @var AbstractEntity */
	private $primaryEntity;

	/** @var  SmartLink */
	private $smartLink;

	/** @var  Template */
	private $template;

	/** @var Version */
	private $version;

	/** @var int ID of the App-Arena project version Id, which is requesting information */
	private $versionId;

	/**
	 * Initialize the App-Manager object
	 *
	 * @param array $options ['apikey'] App-Arena Api Key to authenticate against the API
	 *                       ['appId'] App Id
	 *                       ['cache'] Cache options
	 *                       ['path']
	 *                       ['versionId'] Version ID
	 *                       ['root_path'] Sets the Root path to the app, all path references will be relative to this
	 *
	 * @throws \Exception Any error occuring.
	 */
	public function __construct( array $options = [] ) {
		try {

			// Check if the versionId as required parameter has been set
			if (!isset($options['versionId']) || (int)$options['versionId'] < 1) {
				throw new \InvalidArgumentException('No versionId has been set during the initialization.');
			}
			$this->versionId = (int)$options['versionId'];

			// Get primary Entity to get information for (version, template or app)
			$this->primaryEntity = $this->getPrimaryEntity();

			// Initialize some basic settings
			if ( isset( $options['root_path'] ) ) {
				$this->root_path = $options['root_path'];
			}

			// Initialize some basic settings
			if ( isset( $options['cache']['dir'] ) ) {
				$this->cache_dir = $options['cache']['dir'];
			}

			// Initialize the cache using the primary entity as cache key
			$cacheOptions = isset( $options['cache'] ) ? $options['cache'] : [];
			$this->cache  = new Cache( array_merge_recursive( $cacheOptions, [
				'entityType' => $this->getPrimaryEntity()->getEntityType(),
				'entityId'   => $this->getPrimaryEntity()->getId()
			] ) );

			// Initialize the API connection and set it to the primary entity
			$apiKey    = isset( $options['apikey'] ) ? $options['apikey'] : null;
			$this->api = new Api( [
				'cache'  => $this->cache,
				'apikey' => $apiKey
			] );
			$this->getPrimaryEntity()->setApi( $this->api );
			// Check if Entity exists in App-Manager
			if ( $this->getInfos() === false ) {
				throw new EntityUnknownException( $this->getPrimaryEntity()->getEntityType() . ' with ID "' . $this->getPrimaryEntity()->getId() . '" does not exist.' );
			}

			// Initialize the Environment
			$this->environment = new Models\Environment( $this->primaryEntity );

		} catch ( \Exception $e ) {
			throw $e;
		}

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
	 * @throws EntityUnknownException Throws an exception, when now Entity ID is available
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
			$this->app = new App(null, $this->versionId);
		}

		// If not even an app ID could be instantiated, then throw an exception
		if ( ! $this->app->getId() ) {
			throw new EntityUnknownException( 'No versionId, templateId or appId available. Please submit any of those IDs to establish the App-Manager connection' );
		}

		return $this->app;
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
	 * Returns the currently used Language
	 * @return string Language Code (e.g. de_DE, en_US, ...)
	 */
	public function getLang() {
		return $this->getPrimaryEntity()->getLang();
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
	 * Returns a list of all channels the app has been published on
	 * @return array List of all channels the entity is installed on
	 */
	public function getChannels( ) {
		return $this->getPrimaryEntity()->getChannels();
	}

	/**
	 * Returns user device information
	 */
	public function getDevice() {
		return $this->getEnvironment()->getDevice();
	}

	/**
	 * Returns the device type of the current device 'mobile', 'tablet', 'desktop'
	 */
	public function getDeviceType() {
		return $this->getDevice()->getDeviceType();
	}

	/**
	 * Returns the operating system of the current device
	 */
	public function getOperatingSystem() {
		return $this->getEnvironment()->getOperationSystem();
	}

	/**
	 * Returns all available Facebook information, like currently used fanpage and canvas information
	 */
	public function getFacebook() {
		return $this->getEnvironment()->getFacebook();
	}

	/**
	 * Returns user browser information
	 * @return \UserAgentParser\Model\Browser
	 */
	public function getBrowser() {
		return $this->getEnvironment()->getBrowser();
	}

	/**
	 * @return Environment
	 */
	public function getEnvironment() {
		return $this->environment;
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
	 * @return Api
	 */
	private function getApi() {
		return $this->api;
	}

	/**
	 * Returns the CSS Helper object, which can be used to generate CSS files from Less or config value sources
	 * @return CssCompiler Css helper
	 */
	public function getCssCompiler() {

		if ( ! $this->cssCompiler ) {
			$this->cssCompiler = new CSSCompiler(
				$this->getCache(),
				$this->getPrimaryEntity(),
				$this->getLang(),
				"style",
				$this->root_path,
				$this->cache_dir
			);
		}

		return $this->cssCompiler;
	}

	/**
	 * @param String $file_id Identifier for the file (in most cases defined css-config file)
	 *
	 * @return String returns the CSS filename of the
	 */
	public function getCssFileName( $file_id ) {
		return $this->getCssCompiler()->getCacheKey( $file_id );
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
		return $this->getCssCompiler()->getCSSFiles( $css_config );
	}

	/*/**
	 * @return string
	 */
	/*public function getFilename() {
		return $this->filename;
	}*/

	/**
	 * Sets the filename for the SmartLink (default: smartlink.php)
	 *
	 * @param string $filename
	 */
	/*public function setFilename( $filename ) {
		$this->filename = $filename;
		$this->getSmartLink()->setFilename( $filename );
	}*/

	/**
	 * @return SmartLink Smartlink object
	 *
	 * @throws \Exception
	 */
	public function getSmartLink() {

		if ( ! $this->smartLink ) {
			$this->smartLink = new SmartLink( $this->getPrimaryEntity(), $this->getEnvironment(), $this->getApi()->getCache() );
		}

		return $this->smartLink;
	}

	/**
	 * Invalidate the cache of a submitted entity. See parameter settings in cache section of the documentation
	 *
	 * @param string $action Can be 'all', 'configs', 'infos', 'languages', 'translations', 'apps' or 'templates'
	 */
	public function cacheInvalidate( $action = 'all' ) {
		$this->cache->cacheInvalidate( $action );
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
	 * @return Cache
	 */
	private function getCache() {
		return $this->cache;
	}


}
