<?php

namespace AppManager\SmartLink;

use AppManager\API\Api;
use AppManager\Entity\Instance;
use Browser;
use Detection\MobileDetect;

/**
 * SmartLink class which handles user redirects for the app
 * User: Sebastian Buckpesch (s.buckpesch@iconsultants.eu)
 * Date: 21.05.14
 */
class SmartLink
{
    protected $api; // API object
    protected $instance; // Instance object

    private $base_url;
    private $browser = array(); // Browser information
    private $cookie_key; // SmartCookie key
    private $device = array(); // Device information
    private $facebook = array(); // All available information about the facebook page the instance is embedded in
    private $i_id;
    private $lang; // Currently selected language
    private $meta = array(); // Meta data which should be rendered to the share HTML document
    private $params = array(); // Additional parameters which will be passed through
    private $paramsExpired = array(); // These expired params will not be set to the cookie any more
    private $reasons = array(); // Array of reasons, why the SmartLink refers to a certain environment
    private $target; // Target environment
    private $url; // SmartLink Url
    private $website; // All available information about the website the instance is embedded in

    // Library objects
    private $mustache; // Mustache engine
    private $browser_php; // Browser.php object
    private $mobile_detect; // MobileDetect object

    //private $visitor = null; // SmartVisitor object for the current user
    //private $referrer = null; // SmartReferrer object for the referring user
    //private $db;
    /*private $env = array(); // Environment information

    private $app = array(); //Available information about the app itself

    private $fb = array(); // All facebook related information like signed_request or parsed app requests
    public $smartLinkUrl; // Smart Link
    private $smartLink; // Smart Link object containing most relevant and visitor optimized information*/

    /**
     * Initializes the SmartLink class with visitor, referrer and environment information
     *
     * @param Instance $instance Instance object
     * @throws \Exception When no instance ID is passed
     */
    public function __construct($instance)
    {
        // Initialize the base url
        $base_url  = 'http';
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")
        {
            $base_url .= "s";
        }
        $base_url .= "://";
        $base_url .= $_SERVER["SERVER_NAME"];
        $this->setBaseUrl($base_url);

        // Initialize the instance information
        $this->instance = $instance;
        $this->i_id     = $this->instance->getId();
        if (!$this->i_id)
        {
            throw(new \Exception("No instance id available"));
        }
        $this->cookie_key = "aa_" . $this->i_id . "_smartLink";

        // Initialize Meta data using default values
        $this->setMeta(
            array(
                "title" => "",
                "desc" => "",
                "image" => "",
                "og_type" => "website",
                "schema_type" => "WebApplication",
            )
        );

        // Initializes all language related information
        $this->initLanguage();

        // Initialize the environment information
        $this->initFacebook();
        $this->initWebsite();

        // Collect and prepare the Browser and device information of the current user
        $this->initBrowser();
        $this->initDevice();

        // Initializes the SmartCookie
        $this->initCookies();

        // Initialize the SmartLink Url
        $this->initUrl();
    }

    /**
     * Sets the meta information from the app instance config values
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
        $title       = $desc = $image = "";
        $og_type     = "website";
        $schema_type = "WebApplication";

        // Initialize default values, in case values are missing
        if (isset($meta['title']))
        {
            $title = $meta['title'];
        }
        if (isset($meta['desc']))
        {
            $desc = $meta['desc'];
        }
        if (isset($meta['image']))
        {
            $image = $meta['image'];
        }
        if (isset($meta['og_type']))
        {
            $og_type = $meta['og_type'];
        }
        if (isset($meta['schema_type']))
        {
            $schema_type = $meta['schema_type'];
        }

        // Get values from the instance config values
        if (isset($meta['title']) && $this->instance->getConfig($meta['title']))
        {
            $title = $this->instance->getConfig($meta['title']);
        }
        if (isset($meta['desc']) && $this->instance->getConfig($meta['desc']))
        {
            $desc = $this->instance->getConfig($meta['desc']);
        }
        if (isset($meta['image']) && $this->instance->getConfig($meta['image'], 'src'))
        {
            $image = $this->instance->getConfig($meta['image'], 'src');
        }

        $this->meta = array(
            "title" => $title,
            "desc" => $desc,
            "image" => $image,
            "og_type" => $og_type,
            "schema_type" => $schema_type,
            "url" => $this->getCurrentUrl()
        );

        // Add Open Graph OG meta-data attributes
        $og_meta = array();
        foreach ($meta as $key => $value)
        {
            if (substr($key, 0, 3) == "og:")
            {
                $og_meta[$key] = $value;
            }
        }
        $this->meta['og'] = $og_meta;

        return $this->meta;
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Initializes all available information about Facebook available for the current user and environment
     */
    private function initFacebook()
    {
        // Initialize Facebook Page Tab information
        if ($this->instance->getInfo('fb_page_url') && $this->instance->getInfo('fb_app_id'))
        {
            $fb_page_url = $this->instance->getInfo('fb_page_url') . "?sk=app_" . $this->instance->getInfo('fb_app_id');

            $this->facebook["app_id"]   = $this->instance->getInfo('fb_app_id');
            $this->facebook["page_id"]  = $this->instance->getInfo('fb_page_id');
            $this->facebook["page_url"] = $this->instance->getInfo('fb_page_url');
            $this->facebook["page_tab"] = $fb_page_url;
        }

        // Initializes Facebook canvas information
        if ($this->instance->getInfo('fb_app_id') && $this->instance->getInfo('fb_app_namespace'))
        {
            $this->facebook["app_namespace"] = $this->instance->getInfo('fb_app_namespace');
            $this->facebook["app_id"]        = $this->instance->getInfo('fb_app_id');
            $canvas_url                      = "https://apps.facebook.com/" . $this->facebook["app_namespace"] . "/?i_id=" . $this->i_id;
            $this->facebook["canvas_url"]    = $canvas_url;
        }

        // Get Facebook page tab parameters and write them to GET parameters
        if (isset($_REQUEST["signed_request"]))
        {
            $fb_signed_request = $this->parse_signed_request($_REQUEST["signed_request"]);
            if (isset($fb_signed_request['app_data']))
            {
                $params = json_decode(urldecode($fb_signed_request['app_data']), true);
                foreach ($params as $key => $value)
                {
                    if (!isset($_GET[$key]))
                    {
                        $_GET[$key] = $value;
                    }
                }
            }
        }
    }

    /**
     * Initializes all available information about the website the app is embedded in
     */
    private function initWebsite()
    {
        $website = false;

        // Try to get the website Url from the URL
        if (isset($_GET['website']))
        {
            $website = $_GET['website'];
        }

        // Try to get the website from the cookie
        if (!$website && $this->getCookieValue("website"))
        {
            $website = $this->getCookieValue("website");
        }

        $this->setWebsite($website);
    }

    /**
     * Initializes all available information about the users Browser
     */
    private function initBrowser()
    {
        if (!$this->browser_php)
        {
            $this->browser_php = new Browser();
        }

        $this->browser = array(
            "ua" => $this->browser_php->getUserAgent(),
            "platform" => $this->browser_php->getPlatform(),
            "name" => $this->browser_php->getBrowser(),
            "version" => $this->browser_php->getVersion()
        );
    }

    /**
     * Initializes all available information about the users device
     */
    private function initDevice()
    {
        $device = array();

        if (!$this->mobile_detect)
        {
            $this->mobile_detect = new MobileDetect();
        }

        // Get device type
        $device['type'] = "desktop";
        if ($this->mobile_detect->isMobile())
        {
            $device['type'] = "mobile";
        }
        if ($this->mobile_detect->isTablet())
        {
            $device['type'] = "tablet";
        }

        // Get operating system
        $device['os'] = "other";
        if ($device['type'] == "desktop")
        {
            $device['os'] = $this->getDesktopOs();
        }
        else
        {
            if ($this->mobile_detect->isiOS())
            {
                $device['os'] = "ios";
            }
            if ($this->mobile_detect->isAndroidOS())
            {
                $device['os'] = "android";
            }
            if ($this->mobile_detect->isWindowsMobileOS())
            {
                $device['os'] = "windows";
            }
        }

        $this->device = $device;
    }


    /**
     * Analyzes all available data and sets the best suited target environment for the user
     *
     * @return array All available information about the environments
     */
    private function initUrl()
    {
        $url = false;

        // 1. If a website is defined, use the website as default environment
        if ($this->website)
        {
            $this->reasons[] = "ENV: Website is defined";

            // Validate the Website url
            if (
                strpos($this->website, "www.facebook.com/") !== false ||
                strpos($this->website, "static.sk.facebook.com") !== false ||
                strpos($this->website, ".js") !== false
            )
            {
                $this->reasons[] = "ENV: Website target is not valid, so it cannot be used as target.";
            } else {
                $this->setTarget("website");
                $this->setUrl($this->website);
                return;
            }

        }
        else
        {
            $this->reasons[] = "ENV: No website parameter defined";
        }

        // If there is no website defined, check if the device is tablet or mobile. If so, use direct access
        if (in_array($this->getDeviceType(), array("mobile", "tablet"))) {
            $this->reasons[]   = "DEVICE: User is using a " . $this->getDeviceType() . " device. Direct Access.";
            $this->setTarget("direct");
            $this->setUrl($this->instance->getInfo('base_url'));
            return;
        }

        // So here should be only Desktop devices... So check if facebook page tab information are available...
        $this->reasons[]   = "DEVICE: User is using a desktop device.";
        $facebook = $this->getFacebook();
        if (isset($facebook['page_id']) && $facebook['page_id'] && isset($facebook['app_id']) && $facebook['app_id']) {
            $this->reasons[] = "ENV: Facebook environment data available. Use it as SmartLink";
            $this->setTarget("facebook");
            $this->setUrl($facebook['page_tab']);
            return;
        }

        // If no optimal url is defined yet, then use direct source
        $this->reasons[] = "DEVICE: No website or facebook defined. Choose environment direct";
        $this->setTarget("direct");
        $this->setUrl($this->instance->getInfo('base_url'));
        return;

    }


    /**
     * Initializes the best language for the user
     * @return bool
     */
    private function initLanguage()
    {
        $lang = false;

        // Check if lang GET-parameter is available
        if (!$lang && isset($_GET['lang']))
        {
            $lang            = $_GET['lang'];
            $this->reasons[] = "LANGUAGE: GET['lang']-Parameter available: " . $lang;
        }

        // Check if language cookie is available
        if (!$lang && $this->getCookieValue('lang'))
        {
            $lang            = $this->getCookieValue('lang');
            $this->reasons[] = "LANGUAGE: COOKIE['aa_" . $this->i_id . "_lang']-Parameter available: " . $lang;
        }

        // If no language selected yet, then use the apps default language
        if (!$lang)
        {
            $this->reasons[] = "LANGUAGE: No language preference defined or applicable. Use the default app language.";
            $lang            = $this->instance->getLangTag();
        }

        $this->lang = $lang;

        return $this->lang;
    }


    /**
     * Renders the SmartLink Redirect Share Page
     *
     * @param bool $debug Show debug information on the page?
     */
    public function renderSharePage($debug = false)
    {
        if (!$this->mustache)
        {
            if (!defined("SMART_LIB_PATH"))
            {
                define("SMART_LIB_PATH", realpath(dirname(__FILE__)));
            }
            // Initialize mustache
            $loader   = new \Mustache_Loader_FilesystemLoader(SMART_LIB_PATH . '/views');
            $partials = new \Mustache_Loader_FilesystemLoader(SMART_LIB_PATH . '/views/partials');
            $this->mustache  = new \Mustache_Engine(
                array(
                    'loader' => $loader,
                    'partials_loader' => $partials,
                )
            );
        }

        echo $this->mustache->render(
            'smartLink',
            array(
                'meta' => $this->getMeta(),
                'og_meta' => $this->prepareMustacheArray($this->meta['og']),
                'debug' => $debug,
                'reasons' => $this->reasons,
                'request' => $this->prepareMustacheArray($_REQUEST),
                'smartLinkUrl' => $this->getUrl()
            )
        );
    }

    /**
     * Returns the url of the current script file
     *
     * @param bool $removeParams Should all GET parameters be removed?
     * @return string Url of the current script
     */
    private function getCurrentUrl($removeParams = false)
    {
        $pageURL = 'http';
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on")
        {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

        // Remove GET parameters if wanted
        if ($removeParams)
        {
            $pos     = strpos($pageURL, "?");
            $pageURL = substr($pageURL, 0, $pos);
        }

        return $pageURL;
    }

    /**
     * Set all relevant information as json object (stringified) in a cookie on the visitors computer
     */
    private function initCookies()
    {
        $i_id = $this->i_id;
        $host = $_SERVER['HTTP_HOST'];
        preg_match("/[^\.\/]+\.[^\.\/]+$/", $host, $matches);
        if (count($matches) > 0) {
            $domain = $matches[0];
        } else {
            $domain = $host;
        }

        // Set cookie with the current instance ID
        setcookie("aa_i_id", $i_id, time() + 172600, '/', $domain);

        // Get custom request parameters from Cookie to set them for websites (as they can't be passed into the iframe)
        if (isset($_COOKIE[$this->cookie_key]))
        {
            $cookie_params = json_decode($_COOKIE[$this->cookie_key], true);
            if (is_array($cookie_params) && isset($cookie_params['params']))
            {
                $params = $cookie_params['params'];
                foreach ($params as $key => $value)
                {
                    if (!isset($_GET[$key]))
                    {
                        $_GET[$key] = $value;
                        unset($params[$key]);
                        // Add the param to paramsExpired so that it will not be written to the cookie any more
                        $this->paramsExpired[$key] = $value;
                    }
                }
            }
        }

        // Add all params and GET-Parameters to the cookie as well
        $params = $this->params;
        foreach ($_GET as $key => $value)
        {
            if (!isset($this->paramsExpired[$key]))
            {
                $params[$key] = $value;
            }
        }

        // Set the SmartCookie
        $smart_cookie           = $this->toArray();
        $smart_cookie["params"] = $params;
        setcookie($this->cookie_key, json_encode($smart_cookie), time() + 3600, '/', $domain);

    }

    /**
     * Returns an array of the most important SmartLink information
     */
    /*public function getSmartLink()
    {

        $response = array(
            'meta' => $this->meta,
            'env' => $this->env,
            //'visitor' => $this->visitor->getVisitor(),
            //'referrer' => $this->referrer->getReferrer(),
            'app' => $this->app,
            'reasons' => $this->reasons,
            'request' => $_REQUEST,
            'server' => $_SERVER,
            'smartLinkUrl' => $this->smartLinkUrl
        );

        return $response;
    }*/

    /**
     * @param $url
     *
     * @return mixed
     */
    private function createGoogleShortLink($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, true);
        $parameters = '{"longUrl": "' . $url . '"}';
        curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        $apiKey = 'AIzaSyB90nkbFL6R-eKB47aVY0WLzlcymcssEdI';
        curl_setopt($curl, CURLOPT_URL, 'https://www.googleapis.com/urlshortener/v1/url?key=' . $apiKey);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($curl);
        curl_close($curl);
        $results  = json_decode($data);
        $shortURL = $results->id;

        return $shortURL;
    }

    /**
     * Prepares an associative array to be rendered in mustache. The format changes from
     * array( "key1" => "value1", "key2" => "value2" ) to
     * array( array("key" => "key1", "value" => "value1"), array("key" => "key2", "value" => "value2") )
     *
     * @param $data array Associative array of data
     *
     * @return array Mustache compatible array to be rendered as key value pair
     */
    private function prepareMustacheArray($data)
    {
        $response = array();
        foreach ($data as $key => $value)
        {
            $response[] = array(
                "key" => $key,
                "value" => $value
            );
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
    private function parse_signed_request($signed_request)
    {
        if ($signed_request == false)
        {
            return array();
        }

        //$signed_request = $_REQUEST["signed_request"];
        list($encoded_sig, $payload) = explode('.', $signed_request, 2);
        $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

        return $data;
    }

    /**
     * This will add parameters to the smartLink. These parameters will be available as GET-Parameters, when a user
     * clicks on the smartLink. The parameters will be available as GET parameters as well in the facebook page tab
     *
     * @param Array $params Array of parameters which should be passed through
     */
    public function setParams($params)
    {
        foreach ($params as $key => $value)
        {
            $this->params[$key] = $value;
        }
        $this->initUrl();
    }

    /**
     * Returns user device information
     */
    public function getDevice()
    {
        if (!$this->device)
        {
            $this->device = $this->initDevice();
        }

        return $this->device;
    }

    /**
     * Returns the device type of the current device 'mobile', 'tablet', 'desktop'
     */
    public function getDeviceType()
    {
        if (!$this->device)
        {
            $this->device = $this->getDevice();
        }

        return $this->device['type'];
    }

    /**
     * Returns user browser information
     */
    public function getBrowser()
    {
        if (!$this->browser)
        {
            $this->browser = $this->initBrowser();
        }

        return $this->browser;
    }

    /**
     * @return mixed
     */
    public function getLang()
    {
        if (!$this->lang)
        {
            $this->lang = $this->initLanguage();
        }

        return $this->lang;
    }

    /**
     * Returns the most important smartlink information as array
     * @return array Most important smartlink information
     */
    public function toArray()
    {
        return array(
            "browser" => $this->getBrowser(),
            "device" => $this->getDevice(),
            "facebook" => $this->getFacebook(),
            "i_id" => $this->i_id,
            "lang" => $this->getLang(),
            "m_id" => $this->instance->getMId(),
            "website" => $this->getWebsite()
        );
    }

    /**
     * Returns a value from the SmartCookie
     * @param $key Key to search in the SmartCookie
     * @return Value corresponding to the key
     */
    private function getCookieValue($key)
    {

        if (isset($_COOKIE[$this->cookie_key][$key]))
        {
            return $_COOKIE[$this->cookie_key][$key];
        }

        return false;

    }

    /**
     * @return mixed
     */
    public function getFacebook()
    {
        return $this->facebook;
    }

    /**
     * Sets the website Url the App is embedded in
     * @param mixed $website
     */
    public function setWebsite($website)
    {
        $this->website = $website;
    }

    /**
     * Returns the Url of the website, this app is embedded in
     * @return mixed
     */
    public function getWebsite()
    {
        return $this->website;
    }


    /**
     * Returns the Desktop OS from the UA string
     * @return string
     */
    private function getDesktopOs()
    {

        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $os_platform = "unknown";

        $os_array = array(
            '/windows nt 6.3/i' => 'Windows 8.1',
            '/windows nt 6.2/i' => 'Windows 8',
            '/windows nt 6.1/i' => 'Windows 7',
            '/windows nt 6.0/i' => 'Windows Vista',
            '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
            '/windows nt 5.1/i' => 'Windows XP',
            '/windows xp/i' => 'Windows XP',
            '/windows nt 5.0/i' => 'Windows 2000',
            '/windows me/i' => 'Windows ME',
            '/win98/i' => 'Windows 98',
            '/win95/i' => 'Windows 95',
            '/win16/i' => 'Windows 3.11',
            '/macintosh|mac os x/i' => 'Mac OS X',
            '/mac_powerpc/i' => 'Mac OS 9',
            '/linux/i' => 'Linux',
            '/ubuntu/i' => 'Ubuntu',
            '/iphone/i' => 'iPhone',
            '/ipod/i' => 'iPod',
            '/ipad/i' => 'iPad',
            '/android/i' => 'Android',
            '/blackberry/i' => 'BlackBerry',
            '/webos/i' => 'Mobile'
        );

        foreach ($os_array as $regex => $value)
        {
            if (preg_match($regex, $user_agent))
            {
                $os_platform = $value;
            }
        }

        return $os_platform;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        if (!$this->url) {
            $this->initUrl();
        }

        return $this->url;
    }

    /**
     * Generates the SmartLink from the submitted url
     * @param String $target_url Url to generate the smartlink from
     * @param bool $shortenLink Shorten the SmartLink using bit.ly
     */
    private function setUrl($target_url, $shortenLink = false)
    {

        $filename = "smartlink.php";
        $url = $this->getBaseUrl() . $filename;

        // Add App-Arena Parameters
        $url .= "?i_id=" . $this->instance->getId() . "&m_id=" . $this->instance->getMId();

        // Add the target
        $url .= "&target=" . $this->getTarget();

        // Add the Target Url
        $url .= "&url=" . urlencode($target_url);

        // Add the Language
        $url .= "&lang=" . $this->getLang();

        // Add additional parameters if available in $this->params
        if (count($this->params) > 0)
        {
            $this->params['lang'] = $this->getLang();
            if ($this->getTarget() == "facebook")
            {
                $url .= '&app_data=' . urlencode(json_encode($this->params));
            }
            else
            {
                foreach ($this->params as $key => $value)
                {
                    $url .= "&" . $key . "=" . urlencode($value);
                }
            }
        }

        // Shorten Link
        if ($shortenLink)
        {
            $url = $this->createGoogleShortLink($url);
        }

        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param mixed $target
     */
    private function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->base_url;
    }

    /**
     * @param string $base_url
     */
    public function setBaseUrl($base_url)
    {
        if (substr($base_url, -1) != "/") {
            $base_url .= "/";
        }
        $this->base_url = $base_url;
    }


}
