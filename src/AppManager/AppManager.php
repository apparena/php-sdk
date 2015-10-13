<?php
namespace AppManager;

use AppManager\API\Api;
use AppManager\Entity\Instance;

class AppManager
{

    /**
     * @var
     */
    protected $api; // API object
    protected $cache_dir = false; // E.g. ROOTPATH . /var/cache, When no path is set, then caching will be deactivated
    protected $root_path = false; // Absolute root path of the project on the server
    private $browser;
    private $cookie; // The App-Manager Cookie for the current user
    private $css_helper; // Css Helper object
    private $device;
    private $i_id;
    private $m_id;
    private $lang = "de_DE"; // Language: e.g. de_DE, en_US, en_UK, ...
    /**
     * @var \AppManager\Entity\Instance Instance object
     */
    private $instance;
    /**
     * @var \AppManager\SmartLink\SmartLink SmartLink object
     */
    private $smart_link;
    private $url;

    /**
     * Initialize the App-Manager object
     * @param int   $m_id   Model ID of the current app model
     * @param array $params Parameter for the initialization
     *                      'cache_dir' Cache directory relative to the app source
     * @param int   $i_id   Instance ID if available
     */
    function __construct($m_id, $params = array(), $i_id = null)
    {
        $this->m_id = $m_id;
        $this->i_id = $i_id;

        if (isset($params['root_path'])) {
            $this->root_path = $params['root_path'];
        }

        if (isset($params['cache_dir'])) {
            $this->cache_dir = $this->root_path . $params['cache_dir'];
        }

        $this->init();
    }

    /**
     * Establishes the API connection, current instance and the SmartLink object
     */
    private function init()
    {

        $this->api = new Api(
            array(
                'cache_dir' => $this->cache_dir
            )
        );

        $i_id = $this->getIId();
        $m_id = $this->getMId();

        $this->instance = new Instance(
            $this->api, array(
                          "i_id" => $i_id,
                          "m_id" => $m_id
                      )
        );

        $instance = $this->getInstance();
        $smartLink        = new \AppManager\SmartLink\SmartLink($instance);
        $this->smart_link = $smartLink;

        // Create CSS Helper object
        $this->css_helper = new Helper\Css(
            $this->cache_dir,
            $this->instance,
            "de_DE",
            "style",
            $this->root_path
        );
    }


    /**
     * Returns an attribute of the instance
     * @param String $attr Attribute you want to return
     * @return mixed Requested instance attribute
     */
    function getInfo($attr)
    {
        return $this->instance->getInfo($attr);
    }


    /**
     * Returns all basic information of one instance
     * @return array Basic information about the current instance
     */
    public function getInfos()
    {
        return $this->instance->getInfos();

    }

    /**
     * Returns the value of a config value
     * @param String       $config_id Config identifier to get the data for
     * @param String|array $attr      Attribute or Attributes which should be returned
     * @return mixed Requested config value
     */
    function getConfig($config_id, $attr = "value")
    {
        return $this->instance->getConfig($config_id, $attr);
    }

    /**
     * Returns all Config Elements of the current instance as array
     * @return array All config elements of the current instance
     */
    public function getConfigs()
    {
        return $this->instance->getConfigs();
    }

    /**
     * Returns the translation for the submitted ID
     * @param String $translation_id Config identifier to get the data for
     * @param Array  $args           Array of values to replace in the translation (@see
     *                               http://php.net/manual/de/function.vsprintf.php)
     * @return String Translated value
     */
    function getTranslation($translation_id, $args = array())
    {
        return $this->instance->getTranslation($translation_id, $args);
    }

    /**
     * @return mixed
     */
    public function getTranslations()
    {
        return $this->instance->getTranslations();
    }

    /**
     * Returns the SmartLink Url
     * @param bool $shortenLink Make the Short Link?
     * @return mixed
     */
    public function getUrl($shortenLink = false)
    {
        return $this->smart_link->getUrl($shortenLink);
    }

    /**
     * Returns the Long version of the smartLink
     * @return mixed
     */
    public function getUrlLong()
    {
        return $this->smart_link->getUrlLong();
    }

    /**
     * Returns the currently used Instance ID
     * @return mixed
     */
    public function getIId()
    {
        if ($this->instance) {
            return $this->instance->getId();
        }

        return false;
    }

    /**
     * Returns the currently used Language
     * @return string Language Code (e.g. de_DE, en_US, ...)
     */
    public function getLang()
    {
        return $this->smart_link->getLang();
    }

    /**
     * Sets a new language for the app manager
     * @param string $lang Language Code
     */
    public function setLang($lang)
    {
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
        if (in_array($lang, $allowed)) {
            $this->lang = $lang;
            $this->instance->setLang($this->lang);
        }

    }

    /**
     * Returns the model ID of the currently selected instance
     * @return int Model ID
     */
    public function getMId()
    {
        return $this->m_id;
    }

    /**
     * @return mixed
     */
    private function getInstance()
    {
        return $this->instance;
    }


    /**
     * Renders the complete smartlink.php page
     * @param bool $debug Show debug information on the page?
     */
    public function renderSharePage($debug = false)
    {
        return $this->smart_link->renderSharePage($debug);
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
    public function setMeta($meta)
    {
        return $this->smart_link->setMeta($meta);
    }

    /**
     * This will add parameters to the smartLink Url. These parameters will be available as GET-Parameters, when a user
     * clicks on the smartLink. The parameters will be available as GET parameters as well in the facebook page tab
     * or within an iframe
     *
     * @param Array $params Array of parameters which should be passed through
     */
    public function addParams($params)
    {
        $this->smart_link->addParams($params);
    }

    /**
     * Resets all params for the SmartLink Url
     *
     * @param Array $params Array of parameters which should be passed through
     */
    public function setParams($params)
    {
        $this->smart_link->setParams($params);
    }

    /**
     * Returns user device information
     */
    public function getDevice()
    {
        return $this->smart_link->getDevice();
    }

    /**
     * Returns the device type of the current device 'mobile', 'tablet', 'desktop'
     */
    public function getDeviceType()
    {
        return $this->smart_link->getDeviceType();
    }


    /**
     * Returns all available Facebook information, like currently used fanpage and canvas information
     */
    public function getFacebookInfo()
    {
        return $this->smart_link->getFacebook();
    }

    /**
     * Returns user browser information
     */
    public function getBrowser()
    {
        return $this->smart_link->getBrowser();
    }

    /**
     * Returns if the app currently running on a 'website', 'facebook' or 'direct'
     * 'website' means the app is embedded via iframe to a website
     * 'facebook' means the app is embedded in a facebook page tab
     * 'direct' means the app is being accessed directly without iframe embed
     */
    public function getEnvironment()
    {
        return $this->smart_link->getEnvironment();
    }

    /**
     * Returns the BaseUrl your Sharing Url is generated with. By default it will use the currently used domain
     * @return string Base Url
     */
    public function getBaseUrl()
    {
        return $this->smart_link->getBaseUrl();
    }

    /**
     * Sets a new base url for your sharing links (-->getUrl()).
     * @param string $base_url New base url
     */
    public function setBaseUrl($base_url)
    {
        $this->smart_link->setBaseUrl($base_url);
    }

    /**
     * @return mixed
     */
    public function getCookie()
    {
        return $this->cookie;
    }

    /**
     * @param mixed $cookie
     */
    public function setCookieValue($cookie)
    {
        $this->cookie = $cookie;
    }

    /**
     * @return mixed
     */
    private function getApi()
    {
        return $this->api;
    }

    /**
     * Cleans the cache
     */
    public function cleanCache()
    {
        $this->getApi()->cleanCache("instances/" . $this->getIId());
    }

    /**
     * Returns the CSS Helper object, which can be used to generate CSS files from Less or config value sources
     * @return mixed
     */
    public function getCssHelper()
    {
        return $this->css_helper;
    }


}
