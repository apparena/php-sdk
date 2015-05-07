<?php

namespace AppManager\SmartLink;

use AppManager\API\Api;
use AppManager\Entity\Instance;

/**
 * SmartLink class which handles user redirects for the app
 * User: Sebastian Buckpesch (s.buckpesch@iconsultants.eu)
 * Date: 21.05.14
 */
class SmartLink
{
    private $m; // Mustache engine
    protected $api; // API object
    protected $instance; // Instance object
    protected $instanceInfo; // Instance info array
    protected $instanceConfig; // Instance config array

    private $i_id;
    private $visitor = null; // SmartVisitor object for the current user
    private $referrer = null; // SmartReferrer object for the referring user
    private $db;
    private $env = array(); // Environment information
    private $meta = array(); // Meta data information
    private $app = array(); //Available information about the app itself
    private $params = array(); // Additional parameters which will be passed through
    private $paramsExpired = array(); // These expired params will not be set to the cookie any more
    private $paramsPassthrough = false; // Should all GET parameters of the current request be past through to the next page?
    private $reasons = array(); // Array of reasons, why the SmartLink refers to a certain environment
    private $request = array(); // Array of all request parameters
    private $fb = array(); // All facebook related information like signed_request or parsed app requests
    private $server = array(); // Array of server information
    public $smartLinkUrl; // Smart Link
    private $smartLink; // Smart Link object containing most relevant and visitor optimized information

    /**
     * Initializes the SmartLink class with visitor, referrer and environment information
     *
     * @param array       $data (see above)
     * @param PDODBObject $db   App PDO database object
     * @throws \Exception When no instance ID is passed
     */
    public function __construct($data = array(), $db = null)
    {
        $this->db = $db;
        $this->data = $data;

        if (!defined("SMART_LIB_PATH"))
        {
            define("SMART_LIB_PATH", realpath(dirname(__FILE__)));
        }

        $this->api = new Api(
            array(
                "cache_dir" => ROOT_PATH . "/var/cache"
            )
        );

        // Initialize mustache
        $loader   = new \Mustache_Loader_FilesystemLoader(SMART_LIB_PATH . '/views');
        $partials = new \Mustache_Loader_FilesystemLoader(SMART_LIB_PATH . '/views/partials');
        $this->m  = new \Mustache_Engine(
            array(
                'loader' => $loader,
                'partials_loader' => $partials,
            )
        );
        $this->visitor = new SmartVisitor($this->db);
        $this->referrer = new SmartReferrer($this->db);

        // Check if a fb friend request is available
        $this->fb['app_request'] = $this->parseFBAppRequests();

        // Check if a canvas redirect is necessary
        $this->fb['canvas_request'] = $this->getFBCanvasInformation();

        // Check if parameter passthrough is activated
        if (isset($data['paramsPassthrough']) && $data['paramsPassthrough'] == true)
        {
            $this->paramsPassthrough = true;
        }

        // Try to get the instance ID
        $this->i_id  = $this->getInstanceId();
        // Stop if no instance id is available
        if (!$this->i_id)
        {
            throw(new \Exception("No instance id available"));
        }

        // Check if an language has been submitted via data array.
        if (isset($data['app']['lang']['param']))
        {
            $app['lang']['param'] = $data['app']['lang']['param'];
        }

        // Collect server and request information
        $this->request = $_REQUEST;
        $this->server  = $_SERVER;

        // Initialize instance object
        $this->instance       = new Instance(
            $this->api, array(
                "i_id" => $this->i_id
            )
        );
        $this->instanceInfo   = $this->instance->getInfo();
        $this->instanceConfig = $this->instance->getConfigs();
        $app['aa']['info']   = $this->instanceInfo;
        $app['aa']['config'] = $this->instanceConfig;

        if (isset($data['app']['lang']['available']) &&
            isset($app['aa']['config'][$data['app']['lang']['available']]['value'])
        )
        {
            $lang_str = $app['aa']['config'][$data['app']['lang']['available']]['value'];
            if ($lang_str == "")
            {
                $languanges = array("value" => $app['aa']['info']['lang_tag']);
            }
            else
            {
                $lang_array = explode(",", $lang_str);
                $languanges = array();
                foreach ($lang_array as $lang)
                {
                    $languanges[] = array("value" => $lang);
                }
            }
            $app['lang']['available'] = $languanges;
        }

        $app['lang']['default'] = $this->instanceInfo['lang_tag'];
        if (isset($_REQUEST['request_ids']))
        {
            $app['fb']['request_ids'] = $_REQUEST['request_ids'];
        }
        if (isset($_REQUEST['fb_source']))
        {
            $app['fb']['fb_source'] = $_REQUEST['fb_source'];
        }
        if (isset($data['env']))
        {
            $app['env'] = $data['env'];
        }
        $this->app = $app;

        // Initialize Meta data
        $meta = array();
        if (isset($data['meta']))
        {
            $meta = $data['meta'];
        }
        $this->getMeta($meta);

        // Initialize parameters
        $params = array();
        if (isset($data['params']))
        {
            $params = $data['params'];
        }
        $this->params = $params;

        // Initialize Environments
        $this->getEnv();

        // Set information in user cookie
        $this->setCookie();

        // Set SmartLink
        $this->smartLinkUrl = $this->getSmartLinkUrl();
    }

    /**
     * Gets meta information from the app instance config values
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
    private function getMeta($meta)
    {
        // Initialize default values, in case values are missing
        if (!isset($meta['title']))
        {
            $title = "";
        }
        else
        {
            $title = $meta['title'];
        }
        if (!isset($meta['desc']))
        {
            $desc = "";
        }
        else
        {
            $desc = $meta['desc'];
        }
        if (!isset($meta['image']))
        {
            $image = "";
        }
        else
        {
            $image = $meta['image'];
        }
        if (!isset($meta['og_type']))
        {
            $og_type = "website";
        }
        else
        {
            $og_type = $meta['og_type'];
        }
        if (!isset($meta['schema_type']))
        {
            $schema_type = "WebApplication";
        }
        else
        {
            $schema_type = $meta['schema_type'];
        }

        // Get title from config value
        if (isset($meta['title']) && isset($this->app['aa']['config'][$meta['title']]['value']))
        {
            $title = $this->app['aa']['config'][$meta['title']]['value'];
        }

        if (isset($meta['desc']) && isset($this->app['aa']['config'][$meta['desc']]['value']))
        {
            $desc = $this->app['aa']['config'][$meta['desc']]['value'];
        }

        if (isset($meta['image']) && isset($this->app['aa']['config'][$meta['image']]['src']))
        {
            $image = $this->app['aa']['config'][$meta['image']]['src'];
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
     * Collects all available information about the environments
     *
     * @return array All available information about the environments
     */
    public function getEnv()
    {
        // Get preferred environment
        if (isset($this->app['env']['preferred']))
        {
            $this->env['preferred'] = $this->app['env']['preferred'];
        }

        // Get facebook fanpage information
        if ($this->instanceInfo['fb_page_url'] && $this->instanceInfo['fb_app_id'])
        {
            $fb_page_url = $this->instanceInfo['fb_page_url'] . "?sk=app_" . $this->instanceInfo['fb_app_id'];

            $this->env['fb'] = array(
                "app_id" => $this->instanceInfo['fb_app_id'],
                "page_id" => $this->instanceInfo['fb_page_id'],
                "page_url" => $fb_page_url,
                "url" => $fb_page_url
            );
        }

        // Get custom request parameters from Cookie to set them for websites (as they can't be passed into the iframe else)
        $cookie_key = 'aa_' . $this->i_id . '_referral';
        if (isset($_COOKIE[$cookie_key]))
        {
            $cookie_params = json_decode($_COOKIE[$cookie_key], true);
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

        // Get Facebook page tab parameters and write them to GET parameters
        if (isset($this->request["signed_request"]))
        {
            $fb_signed_request = $this->parse_signed_request($this->request["signed_request"]);
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

        // Get facebook canvas information
        if ($this->instanceInfo['fb_app_id'] && $this->instanceInfo['fb_app_namespace'])
        {
            $base_url                      = "https://apps.facebook.com/" . $this->instanceInfo['fb_app_namespace'] . "/?i_id=" . $this->i_id;
            $this->env['fb']["canvas_url"] = $base_url;
        }

        // Get website information
        if (isset($this->app['env']['website']['url']))
        {
            $url     = "";
            $url_src = "none";
            // Check if website Url is format url
            if (substr($this->app['env']['website']['url'], 0, 4) == "http")
            {
                $url     = $this->app['env']['website']['url'];
                $url_src = "param";
            }
            // Check if config value exists for the website url
            else
            {
                if (isset($app['env']['website']['url']) && isset($app['aa']['config'][$app['env']['website']['url']]['value']))
                {
                    $url     = $app['aa']['config'][$app['env']['website']['url']]['value'];
                    $url_src = "config value";
                }
            }

            $this->env['website'] = array(
                "url" => $url,
                "url_src" => $url_src
            );
        }
        if (isset($this->request['env_website_url']))
        {
            $this->env['website'] = array(
                "url" => urldecode($this->request['env_website_url']),
                "url_src" => "REQUEST parameter"
            );
        }
        else
        {
            if (!isset($this->app['env']['website']['url']) && isset($this->server['HTTP_REFERER']))
            {
                // If no data is available about website integration, but a HTTP REFERRER parameter
                $this->env['website'] = array(
                    "url" => urldecode($this->server['HTTP_REFERER']),
                    "url_src" => "HTTP_REFERER"
                );
            }
        }
        if (isset($_COOKIE['aa_' . $this->i_id . '_env']) && $_COOKIE['aa_' . $this->i_id . '_env'] == "website"
            && isset($_COOKIE['aa_' . $this->i_id . '_env_url'])
        )
        {
            $this->env['website'] = array(
                "url" => $_COOKIE['aa_' . $this->i_id . '_env_url'],
                "url_src" => "cookie"
            );
        }

        // Get direct/mobile information
        $this->env['direct'] = array(
            "url" => $this->instanceInfo['base_url'] . "?i_id=" . $this->i_id
        );

        $this->env['optimal'] = $this->getOptimalEnv();

        return $this->env;
    }


    public function getOptimalLanguage()
    {

        $locale = false;

        // Check if locale GET-parameter is available
        if (!$locale && isset($_GET['locale']))
        {
            $this->reasons[] = "LANGUAGE: GET['locale']-Parameter available: " . $_GET['locale'];
            $locale          = $_GET['locale'];
        }

        // Check if language cookie is available
        if (!$locale && isset($_COOKIE['aa_' . $this->i_id . '_locale']))
        {
            $this->reasons[] = "LANGUAGE: COOKIE['aa_" . $this->i_id . "_locale']-Parameter available: " . $_COOKIE['aa_' . $this->i_id . '_locale'];
            $locale          = $_COOKIE['aa_' . $this->i_id . '_locale'];
        }

        // Check if referrer language is available
        if (!$locale && isset($this->referrer->app['lang']))
        {
            $this->reasons[] = "LANGUAGE: Referrers language is: " . $this->referrer->app['lang'];
            $locale          = $this->referrer->app['lang'];
        }

        if (!$locale && isset($this->visitor->lang) && isset($this->app['lang']['available']))
        {
            $lang_accepted = false;
            foreach ($this->visitor->lang as $lang)
            {
                foreach ($this->app['lang']['available'] as $lang_available)
                {
                    if (strpos($lang_available['value'], $lang) !== false)
                    {
                        $lang_accepted   = true;
                        $this->reasons[] = "LANGUAGE: Users language \"" . $lang . "\" is available in the app as well.";
                        $locale          = $lang_available['value'];
                        break;
                    }
                }
                if ($lang_accepted)
                {
                    break;
                }
            }
        }

        // If no language selected yet, then use the apps default language
        if (!$locale)
        {
            $this->reasons[] = "LANGUAGE: No language preference defined or applicable. Use the default app language.";
            $locale          = $this->app['lang']['default'];
        }

        return $locale;
    }

    /**
     * Tries to initialize the instance ID from several sources
     */
    private function getInstanceId(){
        $i_id = false;

        // Try to get the instance ID from the visitors object
        $i_id    = $this->visitor->getInstanceId();

        // Try to get the instance ID from the referrer object
        if ($this->referrer->getInstanceId())
        {
            $i_id = $this->referrer->getInstanceId();
        }

        // Try to get the instance ID from a cookie
        if (isset($_COOKIE['aa_i_id']))
        {
            $i_id = $_COOKIE['aa_i_id'];
        }

        // Check if an instance ID has been passed during initialization
        if (isset($this->data['app']['i_id']))
        {
            $i_id = $this->data['app']['i_id'];
        }

        return $i_id;
    }

    /**
     * Returns an Array of the optimal env
     */
    public function getOptimalEnv()
    {

        // Get optimal language
        $optimal['lang'] = $this->getOptimalLanguage();

        // Initialize website environment parameters
        $env_website_valid = false;
        $env_website_url   = false;
        if (isset($this->env['website']['url']) && $this->env['website']['url'] != "")
        {
            if (isset($this->env['website']['url_src']) && $this->env['website']['url_src'] == "cookie")
            {
                $this->reasons[]   = "ENV: Website is set in cookie";
                $env_website_valid = true;
            }
            else
            {
                if (isset($this->referrer->app['env']) && $this->referrer->app['env'] == "website")
                {
                    $this->reasons[]   = "ENV: Website is the referrers app environment";
                    $env_website_valid = true;
                }
                else
                {
                    if (isset($this->app['env']['preferred']) && $this->app['env']['preferred'] == "website")
                    {
                        $this->reasons[]   = "ENV: Website is the preferred app environment";
                        $env_website_valid = true;
                    }
                }
            }
            // Set website Url
            if (strpos($this->env['website']['url'], "?") === false)
            {
                $env_website_url = $this->env['website']['url'];
            }
            else
            {
                $env_website_url = $this->env['website']['url'];
            }
            if (strpos($env_website_url, "www.facebook.com/") !== false)
            {
                $this->reasons[] = "ENV: Website target is facebook, which cannot be used as target.";
                $optimal['env']  = "direct";
                $optimal['url']  = $this->env['direct']['url'];
            }
        }
        else
        {
            $this->reasons[] = "ENV: Website Environment not valid";
        }

        // Get device type
        switch ($this->visitor->device['type'])
        {
            case "tablet":
                // TABLET device
                $this->reasons[]   = "DEVICE: User is using a tablet device.";
                $optimal['device'] = "tablet";

                // FACEBOOK environment
                if (isset($this->referrer->app['env']) && $this->referrer->app['env'] == "fb")
                {
                    $this->reasons[] = "ENV: Facebook is not valid for tablets, so do not use it...";
                }

                // WEBSITE environment
                if ($env_website_valid)
                {
                    $optimal['env']  = "website";
                    $optimal['url']  = $env_website_url;
                    $this->reasons[] = "ENV: Website is valid for tablets, so use it...";
                }
                break;

            case "mobile":
                // MOBILE device
                $this->reasons[]   = "DEVICE: User is using a mobile device.";
                $optimal['device'] = "mobile";

                // FACEBOOK environment
                if (isset($this->referrer->app['env']) && $this->referrer->app['env'] == "fb")
                {
                    $this->reasons[] = "ENV: Facebook is not valid for mobile, so do not use it...";
                }

                // WEBSITE environment
                if ($env_website_valid)
                {
                    $optimal['env']  = "website";
                    $optimal['url']  = $env_website_url;
                    $this->reasons[] = "ENV: Website is valid for mobile, so use it...";
                }
                break;

            case "desktop":
                // DESKTOP device
                $this->reasons[]   = "DEVICE: User is using a desktop device.";
                $optimal['device'] = "desktop";

                // WEBSITE environment
                if ($env_website_valid)
                {
                    $optimal['env']  = "website";
                    $optimal['url']  = $env_website_url;
                    $this->reasons[] = "ENV: Website is valid for mobile, so use it...";
                }

                // FACEBOOK environment
                if (isset($this->env['fb']['url']) && $this->env['fb']['url'] != "")
                {
                    if (isset($this->referrer->app['env']) && $this->referrer->app['env'] == "fb")
                    {
                        $this->reasons[] = "ENV: Facebook is the referrers app environment";
                        $optimal['url']  = $this->env['fb']['url'];
                        $optimal['env']  = "fb";
                    }
                    else
                    {
                        // Check if the user is currently on facebook
                        if (isset($this->request['signed_request']))
                        {
                            $this->reasons[] = "ENV: Facebook environment data available, use this as optimal env";
                            $optimal['url']  = $this->env['fb']['url'];
                            $optimal['env']  = "fb";
                        }
                    }
                }
                if (isset($this->app['env']['preferred']) && $this->app['env']['preferred'] == "fb")
                {
                    $this->reasons[] = "ENV: Facebook is the preferred app environment";
                    if (isset($this->env['fb']['url']) && $this->env['fb']['url'])
                    {
                        $optimal['url'] = $this->env['fb']['url'];
                        $optimal['env'] = "fb";
                    }
                }
                break;
        }

        // If no optimal url is defined, then use direct source
        if (!isset($optimal['url']) || $optimal['url'] == "")
        {
            // DIRECT source
            $this->reasons[] = "DEVICE: No preferred or referrer app environment is defined. Choose environment \"direct\"";

            $optimal['url'] = $this->env['direct']['url'];
            $optimal['env'] = "direct";
        }

        // Add parameters to the optimal url, if not website
        if ($optimal['env'] != "website")
        {
            $params = "";
            if (count($this->params) > 0)
            {
                foreach ($this->params as $key => $value)
                {
                    $params .= "&" . $key . "=" . urlencode($value);
                }
            }
            if (isset($optimal['lang']))
            {
                $params .= "&locale=" . $optimal['lang'];
                $this->params['locale'] = $optimal['lang'];
            }
            // If ENV is Facebook, then encode parameters in app_data, else just add the params
            if ($optimal['env'] == "fb")
            {
                $optimal['url'] .= '&app_data=' . urlencode(json_encode($this->params));
            }
            else
            {
                $optimal['url'] .= $params;
            }
        }

        // Check if environment is already is in the cookie. Then use this as optimal environment
        if (isset($_COOKIE["aa_" . $this->i_id . "_env"]))
        {
            $optimal['env'] = $_COOKIE["aa_" . $this->i_id . "_env"];
        }

        return $optimal;
    }

    /**
     * Returns available browser compatibility information
     */
    public function getBrowserCompatibility()
    {
        // Check browser compatibility
        $this->app['browser']['is_compatible'] = "unknown";
        foreach ($this->app['browser']['compatible'] as $browser)
        {
            $browser_found = strpos(
                $this->visitor->browser['name'],
                $browser['name']
            ); // Search browser in user browser
            if ($browser_found !== false)
            {
                if ($this->visitor->browser['version'] < $browser['version'])
                {
                    $this->app['browser']['is_compatible'] = false;
                }
                else
                {
                    $this->app['browser']['is_compatible'] = true;
                }
                break;
            }
        }
    }

    /**
     * Renders the SmartLink page
     *
     * @param bool $debug Show debug information on the page?
     */
    public function render($debug = false)
    {
        echo $this->m->render(
            'smartLink',
            array(
                'meta' => $this->meta,
                'og_meta' => $this->prepareMustacheArray($this->meta['og']),
                'debug' => $debug,
                'env' => $this->env,
                'visitor' => $this->visitor->getVisitor(),
                'referrer' => $this->referrer->getReferrer(),
                'app' => $this->app,
                'reasons' => $this->reasons,
                'request' => $this->prepareMustacheArray($this->request),
                'server' => $this->server,
                'smartLinkUrl' => $this->smartLinkUrl
            )
        );
    }

    /**
     * Returns the url of the current script file
     *
     * @param bool $removeParams Should all GET parameters be removed?
     *
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
    private function setCookie()
    {
        $i_id = $this->i_id;

        // Set cookie with the current instance ID
        setcookie("aa_i_id", $i_id, time() + 172600, '/');

        $cookie_key = "aa_" . $i_id . "_referral";
        $app        = array(
            "i_id" => $i_id,
            "locale" => $this->env['optimal']['lang'],
            "m_id" => $this->instanceInfo['m_id']
        );

        // Add all params and GET-Parameters to the cookie as well, if parameter pass-through is activated
        $params = $this->params;
        if ($this->paramsPassthrough)
        {
            foreach ($_GET as $key => $value)
            {
                if (!isset($this->paramsExpired[$key]))
                {
                    $params[$key] = $value;
                }
            }
        }

        $referrer = array();
        if (isset($this->referrer->app['env']))
        {
            $referrer['env'] = $this->referrer->app['env'];
        }
        if (isset($this->referrer->app['info']['auth_uid']))
        {
            $referrer['auth_uid'] = $this->referrer->app['info']['auth_uid'];
        }

        $value = array(
            'app' => $app,
            'params' => $params
        );
        setcookie($cookie_key, json_encode($value), time() + 3600, '/');

        // Set locale cookie
        if (isset($this->env['optimal']['lang']))
        {
            setcookie("aa_" . $i_id . "_locale", $this->env['optimal']['lang'], time() + 172600, '/');
        }

        // Set environment cookie for websites and facebook
        if (isset($this->env['optimal']['env']) && $this->env['optimal']['env'] != "direct")
        {
            setcookie("aa_" . $i_id . "_env", $this->env['optimal']['env'], time() + 172600, '/');
        }

        // Set device cookie
        if (isset($this->env['optimal']['device']))
        {
            setcookie("aa_" . $i_id . "_device", $this->env['optimal']['device'], time() + 172600, '/');
        }

        // Set environment url
        if (isset($this->env['optimal']['url']))
        {
            setcookie("aa_" . $i_id . "_env_url", $this->env['optimal']['url'], time() + 172600, '/');
        }
    }

    /**
     * Generates the SmartLink for the current visitor
     *
     * @param bool $shortenLink Shorten the SmartLink using bit.ly
     *
     * @return Returns the SmartLink
     */
    public function getSmartLinkUrl($shortenLink = false)
    {
        // Get Link to this page
        //$smartLink = $this->getCurrentUrl(true);

        $smartLink = $this->instanceInfo['base_url'] . "share.php";

        // Add parameters
        $smartLink .= "?i_id=" . $this->i_id;
        if (isset($this->env['optimal']['env']))
        {
            $smartLink .= "&ref_app_env=" . $this->env['optimal']['env'];
        }
        if (isset($this->env['optimal']['lang']))
        {
            $smartLink .= "&locale=" . $this->env['optimal']['lang'];
        }
        if (isset($this->referrer->info['auth_uid']))
        {
            $smartLink .= "&ref_info_uid=" . $this->referrer->info['auth_uid'];
        }
        // Add website url, when url is no script file and no static.sk.facebook.com Url
        if (isset($this->env['website']['url']) && isset($this->env['optimal']['env']) && $this->env['optimal']['env'] == "website")
        {
            // Check if website url comes from an facebook iframe. Remove it then
            if (strpos($this->env['website']['url'], "static.sk.facebook.com") === false)
            {
                // Check if the website url is a script file
                if (strpos($this->env['website']['url'], ".js") === false)
                {
                    $smartLink .= "&env_website_url=" . urlencode($this->env['website']['url']);
                }
            }
        }

        // Add additional parameters if available in $this->params
        if (count($this->params) > 0)
        {
            foreach ($this->params as $key => $value)
            {
                $smartLink .= "&" . $key . "=" . urlencode($value);
            }
        }

        // Shorten Link
        if ($shortenLink)
        {
            $smartLink = $this->createGoogleShortLink($smartLink);
        }

        return $smartLink;
    }

    /**
     * Returns an array of the most important SmartLink information
     */
    public function getSmartLink()
    {

        $response = array(
            'meta' => $this->meta,
            'env' => $this->env,
            'visitor' => $this->visitor->getVisitor(),
            'referrer' => $this->referrer->getReferrer(),
            'app' => $this->app,
            'reasons' => $this->reasons,
            'request' => $this->request,
            'server' => $this->server,
            'smartLinkUrl' => $this->smartLinkUrl
        );

        return $response;
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
     * Tries to get information about the user using the Facebook App Request IDs. A lookup in the database for
     * these request ids is necessary
     * @return array|mixed Returns all available information related to the latest request available or false, when no
     *                     info available
     */
    private function parseFBAppRequests()
    {
        if (!empty($_GET['request_ids']))
        {
            $fb_request_id = explode(",", $_GET['request_ids']);
            // check if more than one ID exists
            if (is_array($fb_request_id) == true)
            {
                $fb_request_id = array_pop($fb_request_id); // the most recent one is the last one
            }

            try
            {
                $sql = "SELECT * FROM mod_facebook_friends WHERE request_id = :request_id LIMIT 1";

                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':request_id', $fb_request_id, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount() > 0)
                {
                    $result = $stmt->fetchObject();

                    return $result;
                }
                else
                {
                    return false;
                }
            } catch (Exception $e)
            {
                return false;
            }
        }

        return false;
    }

    /**
     * Tries to get the latest instance the user was using, by looking it up in the user log
     * @return array|bool Returns the user information and the instance id the user was using lately or false, when no
     *                    info available
     */
    private function getFBCanvasInformation()
    {

        if (!isset($this->i_id) || !$this->i_id)
        {
            $fb_user_id = false;
            $fb_page_id = false;
            // Get FB User ID
            if (isset($_REQUEST["signed_request"]))
            {
                $fb_signed_request = $this->parse_signed_request($_REQUEST["signed_request"]);

                if (isset($fb_signed_request->user_id))
                {
                    $fb_user_id = $fb_signed_request->user_id;
                }

                if (isset($fb_signed_request->page->id))
                {
                    $fb_page_id = $fb_signed_request->page->id;
                }
            }

            // Check if user already participated and get the instance with the most current user interaction
            if ($fb_user_id && !$fb_page_id)
            {
                try
                {
                    $sql = "SELECT mod_log_action.i_id
                            FROM mod_log_action
                            INNER JOIN
                            `user`
                            ON uid=element_id
                            WHERE fb_user_id=:fb_user_id
                            AND scope='user'
                            ORDER BY `timestamp` DESC
                            LIMIT 1";

                    $stmt = $this->db->prepare($sql);
                    $stmt->bindParam(':fb_user_id', $fb_user_id, PDO::PARAM_STR);
                    $stmt->execute();

                    if ($stmt->rowCount() > 0)
                    {
                        $result = $stmt->fetchObject();

                        return $result;
                    }
                    else
                    {
                        return false;
                    }
                } catch (Exception $e)
                {
                    return false;
                }
            }
        }

        return false;
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
        // Now update the optimal url

    }

    /**
     * Returns user device information
     */
    public function getDevice()
    {
        return $this->visitor->getDevice();
    }

    /**
     * Returns user browser information
     */
    public function getBrowser()
    {
        return $this->visitor->getBrowser();
    }
}
