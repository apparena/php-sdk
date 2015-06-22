<?php
namespace AppManager;

use AppManager\API\Api;
use AppManager\Entity\Instance;

class AppManager
{

    protected $api; // API object
    protected $cache_dir = false; // E.g. ROOTPATH . /var/cache, When no path is set, then caching will be deactivated
    private $browser;
    private $device;
    private $i_id;
    private $m_id;
    private $lang = "de_DE"; // Language: e.g. de_DE, en_US, en_UK, ...
    private $info;
    private $instance;
    private $config;
    private $translation;
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

        if (isset($params['cache_dir']))
        {
            $this->cache_dir = $params['cache_dir'];
        }

        $this->init();
    }

    /**
     * Establishes the API connection, current instance and the SmartLink object
     */
    public function init()
    {

        $this->api = new Api(
            array(
                'cache_dir' => $this->cache_dir
            )
        );

        $i_id = $this->getIId();
        $m_id = $this->getMId();
        $lang = $this->getLang();

        $this->instance = new Instance(
            $this->api, array(
                "i_id" => $i_id,
                "m_id" => $m_id,
                "lang" => $lang
            )
        );

        $smartLink        = new \AppManager\SmartLink\SmartLink($this->getInstance());
        $this->smart_link = $smartLink;
    }

    /**
     * Returns all basic information of one instance
     * @return array Basic information about the current instance
     */
    public function getInfos()
    {
        if ($this->info)
        {
            return $this->info;
        }

        if ($this->instance)
        {
            $this->info = $this->instance->getInfos();
            return $this->info;
        }

        return false;

    }

    /**
     * @return mixed
     */
    public function getConfigs()
    {
        if ($this->config)
        {
            return $this->config;
        }

        if ($this->instance)
        {
            $this->config = $this->instance->getConfigs();
            return $this->config;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getTranslations()
    {
        if ($this->translation)
        {
            return $this->translation;
        }


        if ($this->instance)
        {
            $this->translation = $this->instance->getTranslations();
            return $this->translation;
        }

        return false;
    }

    /**
     * Returns the SmartLink Url
     * @return mixed
     */
    public function getUrl()
    {
        if ($this->url)
        {
            return $this->url;
        }
        else
        {
            $this->url = $this->smart_link->getUrl();

            return $this->url;
        }
    }

    /**
     * @return mixed
     */
    public function getIId()
    {
        if ($this->i_id)
        {
            return $this->i_id;
        }

        if ($this->instance)
        {
            $this->i_id = $this->instance->getId();
            return $this->i_id;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @param mixed $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    /**
     * @return int
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
     * Renders the complete share.php page
     * @param bool $debug Show debug information on the page?
     */
    public function renderSharePage($debug = false){

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
    public function setMeta($meta) {
        return $this->smart_link->setMeta($meta);
    }

    /**
     * This will add parameters to the smartLink Url. These parameters will be available as GET-Parameters, when a user
     * clicks on the smartLink. The parameters will be available as GET parameters as well in the facebook page tab
     * or within an iframe
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
     * Returns user browser information
     */
    public function getBrowser()
    {
        return $this->smart_link->getBrowser();
    }


}
