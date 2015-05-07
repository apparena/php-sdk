<?php

namespace AppManager\SmartLink;

class SmartVisitor
{

    protected $md; // Mobile detect object
    protected $bd; // Browser.php detect object

    private $db; // App database connection (if available)

    protected $i_id = false; // Users Instance ID (if available)

    public $info = array(); // Users personal information
    public $browser = array(); // Users browser information
    public $device = array(); // Users device and operating system information
    public $lang = array(); // User supported language in preferred order
    protected $fb = false; // Facebook specific information

    /**
     * Initializes the object with Browser, Device, Language, Facebook and user info
     */
    public function __construct($db = false)
    {
        $this->md = new \Mobile_Detect();
        $this->bd = new \Browser();

        $this->db = $db;

        // Get Facebook specific information
        if (isset($_REQUEST['signed_request']))
        {
            $data_signed_request = explode('.', $_REQUEST['signed_request']); // Get the part of the signed_request we need.
            $this->fb            = json_decode(base64_decode($data_signed_request['1']), true); // Split the JSON into arrays.
        }

        // Initialize user information
        $this->getInfo();

        // Initialize users instance ID (search in the app database)
        $this->getInstanceId();

        // Browser detection
        $this->getBrowser();

        // Device detection
        $this->getDevice();

        // Language detection
        $this->getLanguages();

        /*
        // Set cookie/localstorage on the users computer
        // Create log entries
        */

    }

    /**
     * Initializes all available language information about the current visitor
     */
    public function getLanguages()
    {
        $languages = array();

        // 1. Get language from HTTP request
        if (isset($_REQUEST['locale']) && strlen($_REQUEST['locale']) >= 2)
        {
            $languages[] = substr($_REQUEST['locale'], 0, 2);
            $this->browser['request']['lang'] = $_REQUEST['locale'];
        }

        // 2. Get language from facebook parameter
        if ( isset( $this->fb['user']['locale'] ) ) {
            $languages[] = substr($this->fb['user']['locale'], 0, 2);
        }

        // 3. Get users preferred browser languages
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
        {
            $http_languages    = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $browser_languages = array();
            foreach ($http_languages as $http_language)
            {
                $language = substr($http_language, 0, 2);
                if (!in_array($language, $languages))
                {
                    $languages[]         = $language;
                    $browser_languages[] = $language;
                }
            }
            $this->browser['lang'] = $browser_languages;
        }
        $this->lang = $languages;

        return $this->lang;
    }

    /**
     * Collects all available device information and returns them in an array
     *
     * @return array Available device information
     */
    public function getDevice()
    {
        // Get device type
        $this->device['type'] = "desktop";
        if ( $this->md->isMobile() ) {
            $this->device['type'] = "mobile";
        }
        if ( $this->md->isTablet() ) {
            $this->device['type'] = "tablet";
        }

        // Get operating system
        $this->device['os'] = "other";
        if ( $this->device['type'] == "desktop") {
            $this->device['os'] = $this->getDesktopOs();
        } else {
            if( $this->md->isiOS() ){
                $this->device['os'] = "ios";
            }
            if( $this->md->isAndroidOS() ){
                $this->device['os'] = "android";
            }
            if( $this->md->isWindowsMobileOS() ){
                $this->device['os'] = "windows";
            }
        }


        return $this->device;
    }

    private function getDesktopOs() {

        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $os_platform    =   "unknown";

        $os_array       =   array(
            '/windows nt 6.3/i'     =>  'Windows 8.1',
            '/windows nt 6.2/i'     =>  'Windows 8',
            '/windows nt 6.1/i'     =>  'Windows 7',
            '/windows nt 6.0/i'     =>  'Windows Vista',
            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'     =>  'Windows XP',
            '/windows xp/i'         =>  'Windows XP',
            '/windows nt 5.0/i'     =>  'Windows 2000',
            '/windows me/i'         =>  'Windows ME',
            '/win98/i'              =>  'Windows 98',
            '/win95/i'              =>  'Windows 95',
            '/win16/i'              =>  'Windows 3.11',
            '/macintosh|mac os x/i' =>  'Mac OS X',
            '/mac_powerpc/i'        =>  'Mac OS 9',
            '/linux/i'              =>  'Linux',
            '/ubuntu/i'             =>  'Ubuntu',
            '/iphone/i'             =>  'iPhone',
            '/ipod/i'               =>  'iPod',
            '/ipad/i'               =>  'iPad',
            '/android/i'            =>  'Android',
            '/blackberry/i'         =>  'BlackBerry',
            '/webos/i'              =>  'Mobile'
        );

        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $os_platform    =   $value;
            }
        }

        return $os_platform;
    }

    /**
     * Returns the users browser information
     * @return array Browser information array
     */
    public function getBrowser()
    {
        $this->browser['ua'] = $this->bd->getUserAgent();
        $this->browser['name'] = $this->bd->getBrowser();
        $this->browser['version'] = floor($this->bd->getVersion());

        return $this->browser;
    }

    /**
     * Returns user personal data
     * @return array Personal user information
     */
    public function getInfo()
    {
        // Get information from facebook
        if ( isset($this->fb['user_id']) ){
            $this->info['fb_user_id'] = $this->fb['user_id'];
        }

        // Get extended information about the user from facebook
        if ( isset($this->fb['oauth_token'] ) ) {
            $request_url = "https://graph.facebook.com/me?method=GET&format=json&suppress_http_code=1&access_token=";
            $fb_user = json_decode(file_get_contents($request_url . $this->fb['oauth_token']),true);

            // Get gender
            if ( isset($fb_user['gender']) ) {
                $this->info['gender'] = $fb_user['gender'];
            }

            // Get firstname
            if ( isset($fb_user['first_name']) ) {
                $this->info['first_name'] = $fb_user['first_name'];
            }

            // Get lastname
            if ( isset($fb_user['last_name']) ) {
                $this->info['last_name'] = $fb_user['last_name'];
            }

            // Get email
            if ( isset($fb_user['email']) ) {
                $this->info['email'] = $fb_user['email'];
            }
        }

        return $this->info;
    }

    /**
     * Returns the whole visitor object
     */
    public function getVisitor()
    {
        $user = array(
            "info"      => $this->getInfo(),
            "lang" => $this->getLanguages(),
            "browser"   => $this->getBrowser(),
            "device"    => $this->getDevice(),
            "fb"        => $this->fb,
            "i_id"      => $this->i_id
        );

        return $user;
    }

    /**
     * Returns the users main instance ID
     */
    public function getInstanceId($db_table_name = "mod_auth_user", $db_fb_user_id_col = "fb_id") {

        /*if ( $this->db && isset($this->info['fb_user_id']) ) {
            // Get instance id and
            $sql = "SELECT * FROM " . $db_table_name .  " WHERE " . $db_fb_user_id_col . " = :fb_user_id LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':fb_user_id', $this->info['fb_user_id'], PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0)
            {
                $result = $stmt->fetchObject();
                if (isset($result->i_id))
                {
                    $this->i_id = $result->i_id;
                    return $this->i_id;
                }
            }
        }*/

        $this->i_id = false;
        return $this->i_id;
    }

}