<?php

namespace AppManager\SmartLink;

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
    private $cookie_domain; // Domain to use for the cookie
    private $device = array(); // Device information
    private $environment; // Target environment
    private $facebook = array(); // All available information about the facebook page the instance is embedded in
    private $i_id;
    private $lang; // Currently selected language
    private $meta = array(); // Meta data which should be rendered to the share HTML document
    private $paramsAdditional = array(); // Additional parameters which will be passed through
    private $paramsExpired = array(); // These expired params will not be set to the cookie any more
    private $reasons = array(); // Array of reasons, why the SmartLink refers to a certain environment
    private $target; // If a target is defined, then this will be used as preferred redirect location
    private $url; // SmartLink Url (Url for sharing)
    private $url_long; // SmartLink Url in long form
    private $url_short; // ShartLink Url processed by an url shortener
    private $url_target; // The url the user will be redirected to
    private $website; // All available information about the website the instance is embedded in

    // Library objects
    private $mustache; // Mustache engine
    private $browser_php; // Browser.php object
    private $mobile_detect; // MobileDetect object

    /**
     * Initializes the SmartLink class with visitor, referrer and environment information
     *
     * @param Instance $instance Instance object
     * @throws \Exception When no instance ID is passed
     */
    public function __construct(&$instance)
    {
        // Initialize the base url
        $this->initBaseUrl();

        // Initialize the instance information
        $this->instance = $instance;
        $this->i_id     = $this->instance->getId();
        if (!$this->i_id) {
            throw(new \Exception('No instance id available'));
        }
        $this->cookie_key = 'aa_' . $this->i_id . '_smartlink';

        // Initialize Meta data using default values
        $this->setMeta(
            array(
                'title' => '',
                'desc' => '',
                'image' => '',
                'og_type' => 'website',
                'schema_type' => 'WebApplication',
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
        $title       = $desc = $image = '';
        $og_type     = 'website';
        $schema_type = 'WebApplication';

        // Initialize default values, in case values are missing
        if (isset($meta['title'])) {
            $title = $meta['title'];
        }
        if (isset($meta['desc'])) {
            $desc = $meta['desc'];
        }
        if (isset($meta['image'])) {
            $image = $meta['image'];
        }
        if (isset($meta['og_type'])) {
            $og_type = $meta['og_type'];
        }
        if (isset($meta['schema_type'])) {
            $schema_type = $meta['schema_type'];
        }

        // Get values from the instance config values
        if (isset($meta['title']) && $this->instance->getConfig($meta['title'])) {
            $title = $this->instance->getConfig($meta['title']);
        }
        if (isset($meta['desc']) && $this->instance->getConfig($meta['desc'])) {
            $desc = $this->instance->getConfig($meta['desc']);
        }
        if (isset($meta['image']) && $this->instance->getConfig($meta['image'], 'src')) {
            $image = $this->instance->getConfig($meta['image'], 'src');
        }

        $this->meta = array(
            'title' => $title,
            'desc' => $desc,
            'image' => $image,
            'og_type' => $og_type,
            'schema_type' => $schema_type,
            'url' => $this->getCurrentUrl()
        );

        // Add Open Graph OG meta-data attributes
        $og_meta = array();
        foreach ($meta as $key => $value) {
            if (substr($key, 0, 3) == 'og:') {
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
     * Initializes the baseUrl of the current app
     */
    private function initBaseUrl()
    {
        // Initialize the base_url
        $base_url = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $base_url .= 's';
        }
        $base_url .= '://';
        $base_url .= $_SERVER['SERVER_NAME'];
        if (substr($base_url, -1) != '/') {
            $base_url .= '/';
        }
        $this->base_url = $base_url;

        $url             = parse_url($_SERVER['REQUEST_URI']);
        $path_parts      = pathinfo($url['path']);
        $base_path       = $path_parts['dirname'];
        $this->base_path = $base_path;

        // Initialize the domain and cookie domain
        $host = $_SERVER['HTTP_HOST'];
        preg_match("/[^\.\/]+\.[^\.\/]+$/", $host, $matches);
        if (count($matches) > 0) {
            $domain = $matches[0];
        } else {
            $domain = $host;
        }
        $this->cookie_domain = "." . $domain;
        if ($domain == 'localhost') {
            $this->cookie_domain = null;
        }

    }

    /**
     * Initializes all available information about Facebook available for the current user and environment
     */
    private function initFacebook()
    {
        // Initialize Facebook Page Tab information
        if ($this->instance->getInfo('fb_page_url') && $this->instance->getInfo('fb_app_id')) {
            $fb_page_url = $this->instance->getInfo('fb_page_url') . '?sk=app_' . $this->instance->getInfo('fb_app_id');

            $this->facebook['app_id']   = $this->instance->getInfo('fb_app_id');
            $this->facebook['page_id']  = $this->instance->getInfo('fb_page_id');
            $this->facebook['page_url'] = $this->instance->getInfo('fb_page_url');
            $this->facebook['page_tab'] = $fb_page_url;
        }

        // Initializes Facebook canvas information
        if ($this->instance->getInfo('fb_app_id') && $this->instance->getInfo('fb_app_namespace')) {
            $this->facebook['app_namespace'] = $this->instance->getInfo('fb_app_namespace');
            $this->facebook['app_id']        = $this->instance->getInfo('fb_app_id');
            $canvas_url                      = 'https://apps.facebook.com/' . $this->facebook['app_namespace'] . '/?i_id=' . $this->i_id;
            $this->facebook['canvas_url']    = $canvas_url;
        }

        // Get Facebook page tab parameters and write them to GET parameters
        if (isset($_REQUEST['signed_request'])) {
            $this->facebook['signed_request'] = $_REQUEST['signed_request'];
            $fb_signed_request = $this->parse_signed_request($_REQUEST['signed_request']);
            if (isset($fb_signed_request['app_data'])) {
                $params = json_decode(urldecode($fb_signed_request['app_data']), true);
                foreach ($params as $key => $value) {
                    if (!isset($_GET[$key])) {
                        $_GET[$key] = $value;
                    }
                }
            }
        } else {
            $this->facebook['signed_request'] = false;
        }
    }

    /**
     * Initializes all available information about the website the app is embedded in
     */
    private function initWebsite()
    {
        $website = false;

        // Try to get the website Url from the URL
        if (isset($_GET['website'])) {
            $website = $_GET['website'];
        }

        // Try to get the website from the cookie
        if (!$website && $this->getCookieValue('website')) {
            $website = $this->getCookieValue('website');
        }

        $this->setWebsite($website);
    }

    /**
     * Initializes all available information about the users Browser
     */
    private function initBrowser()
    {
        if (!$this->browser_php) {
            $this->browser_php = new Browser();
        }

        $this->browser = array(
            'ua' => $this->browser_php->getUserAgent(),
            'platform' => $this->browser_php->getPlatform(),
            'name' => $this->browser_php->getBrowser(),
            'version' => $this->browser_php->getVersion()
        );

    }

    /**
     * Initializes all available information about the users device
     */
    private function initDevice()
    {
        $device = array();

        if (!$this->mobile_detect) {
            $this->mobile_detect = new MobileDetect();
        }

        // Get device type
        $device['type'] = 'desktop';
        if ($this->mobile_detect->isMobile()) {
            $device['type'] = 'mobile';
        }
        if ($this->mobile_detect->isTablet()) {
            $device['type'] = 'tablet';
        }

        // Get operating system
        $device['os'] = 'other';
        if ($device['type'] == 'desktop') {
            $device['os'] = $this->getDesktopOs();
        } else {
            if ($this->mobile_detect->isiOS()) {
                $device['os'] = 'ios';
            }
            if ($this->mobile_detect->isAndroidOS()) {
                $device['os'] = 'android';
            }
            if ($this->mobile_detect->isWindowsMobileOS()) {
                $device['os'] = 'windows';
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

        // Due to Safari Cookie Blocking policies, redirect Safari Users to the direct page
        /*$browser = $this->getBrowser();
        if ($browser['name'] == 'Safari') {
            $this->reasons[] = 'BROWSER: Safari is used. Change target to direct';
            $this->setTarget('direct');
        }*/

        // 1. If a website is defined, use the website as default environment
        if ($this->website) {
            $this->reasons[] = 'ENV: Website is defined';

            // Validate the Website url
            $website_valid = true;
            if (
                strpos($this->website, 'www.facebook.com/') !== false ||
                strpos($this->website, 'static.sk.facebook.com') !== false ||
                strpos($this->website, '.js') !== false
            ) {
                $this->reasons[] = 'ENV: Website target is not valid, so it cannot be used as target.';
                $website_valid   = false;
            }

            // Check if another target is defined, then add the website as GET param, but do not use it for redirection
            if ($this->getUrlTarget() && $this->getTarget() != 'website') {
                $this->reasons[] = 'ENV: Website valid, but another target is defined';
                $this->addParams(array('website' => $this->website));
                $website_valid = false;
            }

            // If Website is valid, then use it
            if ($website_valid) {
                $this->setEnvironment('website');
                $this->setUrl($this->website);

                return;
            }
        } else {
            $this->reasons[] = 'ENV: No website parameter defined';
        }

        // If there is no website defined, check if the device is tablet or mobile. If so, use direct access
        if (in_array($this->getDeviceType(), array('mobile', 'tablet'))) {
            $this->reasons[] = 'DEVICE: User is using a ' . $this->getDeviceType() . ' device. Direct Access.';
            $this->setEnvironment('direct');
            $this->setUrl($this->instance->getInfo('base_url'));

            return;
        }

        // So here should be only Desktop devices... So check if facebook page tab information are available...
        $this->reasons[] = 'DEVICE: User is using a desktop device.';
        $facebook        = $this->getFacebook();
        if (isset($facebook['page_id']) && $facebook['page_id'] && isset($facebook['app_id']) && $facebook['app_id']) {
            $this->reasons[] = 'ENV: Facebook environment data available.';

            // Check if another target is defined, then add the website as GET param, but do not use it for redirection
            $facebook_valid = true;
            if ($this->getUrlTarget() && $this->getTarget() != 'facebook') {
                $this->reasons[] = 'ENV: Facebook environment valid, but another target is defined';
                $facebook_valid  = false;
            }

            // If Facebook Environment is valid we are currently on Facebook, then use it
            if ($facebook_valid && $facebook['signed_request']) {
                $this->setEnvironment('facebook');
                $this->setUrl($facebook['page_tab']);

                return;
            }

        }

        // If no optimal url is defined yet, then use direct source
        $this->reasons[] = 'DEVICE: No website or facebook defined. Choose environment direct';
        $this->setEnvironment('direct');
        if ($this->getBaseUrl()) {
            $this->setUrl($this->getBaseUrl());
        } else {
            $this->setUrl($this->instance->getInfo('base_url'));
        }

        return;

    }

    /**
     * Initializes the best language for the user
     * @return bool
     */
    private function initLanguage()
    {
        $lang = false;

        // Try to get the language from the REQUEST
        if (isset($_REQUEST['lang'])) {
            $lang            = $_REQUEST['lang'];
            $this->reasons[] = "LANGUAGE: REQUEST['lang']-Parameter available: " . $lang;
        } else {
            // Check if the language is configured in the VirtualHost
            if (isset($_SERVER['lang'])) {
                $lang            = $_SERVER['lang'];
                $this->reasons[] = "LANGUAGE: GET['lang']-Parameter available: " . $lang;
            } else {
                // Check if language cookie is available
                if ($this->getCookieValue('lang')) {
                    $lang            = $this->getCookieValue('lang');
                    $this->reasons[] = "LANGUAGE: COOKIE['aa_' . $this->i_id . '_lang']-Parameter available: " . $lang;
                } else {
                    // If no language selected yet, then use the apps default language
                    $this->reasons[] = 'LANGUAGE: No language preference defined or applicable. Use the default app language.';
                    $lang            = $this->instance->getLang();
                }
            }
        }

        $this->lang = $lang;
        $this->instance->setLang($lang);

        return $this->lang;
    }


    /**
     * Renders the SmartLink Redirect Share Page
     *
     * @param bool $debug Show debug information on the page?
     */
    public function renderSharePage($debug = false)
    {
        if (!$this->mustache) {
            if (!defined('SMART_LIB_PATH')) {
                define('SMART_LIB_PATH', realpath(dirname(__FILE__)));
            }
            // Initialize mustache
            $loader         = new \Mustache_Loader_FilesystemLoader(SMART_LIB_PATH . '/views');
            $partials       = new \Mustache_Loader_FilesystemLoader(SMART_LIB_PATH . '/views/partials');
            $this->mustache = new \Mustache_Engine(
                array(
                    'loader' => $loader,
                    'partials_loader' => $partials,
                )
            );
        }

        $data = array(
            'browser' => $this->getBrowser(),
            'cookies' => $this->prepareMustacheArray($_COOKIE),
            'debug' => $debug,
            'device' => $this->getDevice(),
            'i_id' => $this->i_id,
            'info' => $this->instance->getInfos(),
            'lang' => $this->getLang(),
            'meta' => $this->getMeta(),
            'og_meta' => $this->prepareMustacheArray($this->meta['og']),
            'params' => $this->prepareMustacheArray($this->getParams()),
            'params_expired' => $this->prepareMustacheArray($this->paramsExpired),
            'reasons' => $this->reasons,
            'target' => $this->getEnvironment(),
            'url' => $this->getUrl(),
            'url_target' => $this->getUrlTarget()
        );
        echo $this->mustache->render('share', $data);
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
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $pageURL .= 's';
        }
        $pageURL .= '://';
        $pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

        // Remove GET parameters if wanted
        if ($removeParams) {
            $pos     = strpos($pageURL, '?');
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

        // Set cookie with the current instance ID
        setcookie('aa_i_id', $i_id, time() + 172600, '/', $this->cookie_domain);

        // Iframe Parameter Passthrough
        // 1. Get parameters from Cookie
        $params        = $this->getCookieValue('params');
        $paramsExpired = array();
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                if (!isset($_GET[$key])) {
                    // 1.1 Write parameters from the cookie to the Request and set them expired after that
                    $_GET[$key]          = $value;
                    $paramsExpired[$key] = $value;
                }
            }
        } else {
            $params = array();
        }
        $this->paramsExpired = $paramsExpired;

        $this->addParams($params);

        // Set the SmartCookie
        $smart_cookie = $this->toArray();
        $this->setCookieValues($smart_cookie);

    }


    /**
     * @param $url
     *
     * @return mixed
     */
    private function createGoogleShortLink($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, true);
        $parameters = "{'longUrl': '' . $url . ''}";
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
     * Creates a Short Url using App-Arena Url Shortener
     * @param Strin $url Long Url
     *
     * @return String Short Url
     */
    private function shortenLink($url)
    {
        $timestamp = time();
        $signature = md5($timestamp . '2ff4988406');
        $api_url   = 'http://smartl.ink/yourls-api.php';

        // Init the CURL session
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result
        curl_setopt($ch, CURLOPT_POST, 1);              // This is a POST request
        curl_setopt($ch,
                    CURLOPT_POSTFIELDS,
                    array(     // Data to POST
                        'url' => $url,
                        'format' => 'json',
                        'action' => 'shorturl',
                        'timestamp' => $timestamp,
                        'signature' => $signature
                    ));

        // Fetch and return content
        $data = curl_exec($ch);
        curl_close($ch);

        // Do something with the result. Here, we echo the long URL
        $data = json_decode($data);

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
    private function prepareMustacheArray($data)
    {
        $response = array();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            $response[] = array(
                'key' => $key,
                'value' => $value
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
        if ($signed_request == false) {
            return array();
        }

        //$signed_request = $_REQUEST['signed_request'];
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
    public function addParams($params)
    {
        foreach ($params as $key => $value) {
            $this->paramsAdditional[$key] = $value;
        }
    }

    /**
     * Overwrites all existing parameters
     *
     * @param Array $params Array of parameters which should be passed through
     */
    public function setParams($params)
    {
        $this->paramsAdditional = $params;
    }

    /**
     * Returns user device information
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * Returns the device type of the current device 'mobile', 'tablet', 'desktop'
     */
    public function getDeviceType()
    {
        if (isset($this->device['type'])) {
            return $this->device['type'];
        }

        return false;
    }

    /**
     * Returns user browser information
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    /**
     * @return mixed
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Returns the most important smartlink information as array
     * @return array Most important smartlink information
     */
    public function toArray()
    {
        return array(
            'browser' => $this->getBrowser(),
            'device' => $this->getDevice(),
            'facebook' => $this->getFacebook(),
            'i_id' => $this->i_id,
            'params' => $this->getParams(),
            'paramsExpired' => $this->paramsExpired,
            'lang' => $this->getLang(),
            'm_id' => $this->instance->getMId(),
            'website' => $this->getWebsite()
        );
    }

    /**
     * Returns a value from the SmartCookie
     * @param String $key to search in the SmartCookie
     * @return mixed Value corresponding to the key
     */
    private function getCookieValue($key)
    {
        if (isset($_COOKIE[$this->cookie_key])) {
            // Decode cookie value
            $cookie         = $_COOKIE[$this->cookie_key];
            $cookie_decoded = json_decode($cookie, true);
            if (isset($cookie_decoded[$key])) {
                return $cookie_decoded[$key];
            }
        }

        return false;

    }


    /**
     * Sets values to the SmartCookie
     * @param array $values     Array of key value pairs which should be added to the Smart-Cookie cookie
     * @param int   $expiration Number of seconds until the cookie will expire
     * @return array Returns the whole updated cookie as array
     */
    private function setCookieValues($values, $expiration = 7200)
    {
        $cookie = array();
        if (isset($_COOKIE[$this->cookie_key])) {
            $cookie = json_decode($_COOKIE[$this->cookie_key], true);
        }

        if (!is_array($cookie)) {
            $cookie = array();
        }

        foreach ($values as $key => $value) {
            $cookie[$key] = $value;
        }

        // Write the cookie to the users cookies
        $cookie_encoded = json_encode($cookie);

        setcookie($this->cookie_key, $cookie_encoded, time() + $expiration, '/', $this->cookie_domain);

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
        if ($website) {
            $this->website = $website;
            $this->addParams(array('website' => $this->website));
        }
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
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return 'unknown';
        }

        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $os_platform = 'unknown';

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

        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $os_platform = $value;
            }
        }

        return $os_platform;
    }

    /**
     * @params bool $shortenLink Make a Short Link?
     * @return mixed
     */
    public function getUrl($shortenLink = false)
    {
        $this->initUrl();

        if ($shortenLink) {
            $this->url = $this->shortenLink($this->url);
        }

        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getUrlLong()
    {
        return $this->url_long;
    }

    /**
     * Generates the SmartLink from the submitted url
     * @param String $target_url  Url to generate the smartlink from
     * @param bool   $shortenLink Shorten the SmartLink using bit.ly
     */
    private function setUrl($target_url, $shortenLink = false)
    {
        $filename        = 'smartlink.php';
        $share_url       = $this->getBaseUrl() . $filename;
        $target_original = $target_url;

        $params = array();

        // Add App-Arena Parameters
        $params['i_id'] = $this->instance->getId();
        $params['m_id'] = $this->instance->getMId();
        $params['lang'] = $this->getLang();

        // Add additional parameters if available in $this->params
        $params = array_merge($this->paramsAdditional, $params);

        // Generate sharing and target Url
        foreach ($params as $key => $value) {
            if ($value != "") {
                if (is_array($value)) {
                    $value = json_encode($value);
                }

                // If it is the first parameter, then use '?', else use  '&'
                if (strpos($target_url, '?') === false) {
                    $target_url .= '?' . $key . '=' . urlencode($value);
                } else {
                    $target_url .= '&' . $key . '=' . urlencode($value);
                }
                if (strpos($share_url, '?') === false) {
                    $share_url .= '?' . $key . '=' . urlencode($value);
                } else {
                    $share_url .= '&' . $key . '=' . urlencode($value);
                }
            }
        }

        if ($this->getEnvironment() == 'facebook') {
            $target_url = $target_original . '&app_data=' . urlencode(json_encode($params));
        }

        // Shorten Link, when the link changed...
        if ($shortenLink && $this->url_long != $share_url) {
            $this->url_long = $share_url;
            $share_url = $this->createGoogleShortLink($share_url);
            $this->url_short = $share_url;
        } else {
            $this->url_long = $share_url;
        }

        $this->url = $share_url;
        $this->setUrlTarget($target_url);
    }

    /**
     * @return mixed
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @param mixed $environment
     */
    private function setEnvironment($environment)
    {
        $allowed = array('website', 'facebook', 'direct');

        if (in_array($environment, $allowed)) {
            $this->environment = $environment;
        }
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
        if (substr($base_url, -1) != '/') {
            $base_url .= '/';
        }
        $this->base_url = $base_url;
    }

    /**
     * @return mixed
     */
    public function getUrlTarget()
    {
        return $this->url_target;
    }

    /**
     * @param mixed $url_target
     */
    public function setUrlTarget($url_target)
    {
        $this->url_target = $url_target;
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
    public function setTarget($target)
    {
        $allowed = array('website', 'facebook', 'direct');

        if (in_array($target, $allowed)) {
            $this->target = $target;
        }

    }

    /**
     * Prepare the params.
     * @return array
     */
    public function getParams()
    {
        $params = array_merge($_GET, $this->paramsAdditional);

        // Remove expired params
        foreach ($this->paramsExpired as $key => $value) {
            if (isset($params[$key])) {
                unset($params[$key]);
            }
        }

        return $params;
    }

}
