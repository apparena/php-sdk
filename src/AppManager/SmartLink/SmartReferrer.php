<?php
/**
 * Returns as much information about the referrer as possible. If no instance id exists, this class does offer
 * methods to get it by facebook request id
 */

namespace AppManager\SmartLink;

class SmartReferrer
{
    private $i_id = false; // Referral instance ID
    public $auth_uid = false; // Internal App User ID of the refferer
    public $info = array(); // Users personal information
    private $fb;    // Available facebook information
    private $db; // App database connection
    public $app; // App settings for the referral user (Language, Environment (Website, Fanpage, Direct)

    public function __construct($db = false)
    {
        // Sets db object
        $this->db = $db;

        // 1. Try to get instance id from facebook request id (DB Lookup)
        if ( isset($_GET['request_ids']) && $this->db )
        {
            $this->decodeFbRequestId($_GET['request_ids']);
        }

        // Try to get more referrer personal data (DB Lookup)
        $this->getInfo();

        // Try to get more information about the app settings of the referrer
        $this->getApp();


        // 2. Try to get the instance id from the current fb user id (DB Lookup)
        if (isset($_REQUEST['signed_request']) )
        {
            $data_signed_request = explode('.', $_REQUEST['signed_request']); // Get the part of the signed_request we need.
            $this->fb            = json_decode(base64_decode($data_signed_request['1']), true); // Split the JSON into arrays.

            //@todo get the instance id from the current fb user id (DB Lookup)
        }

        // 3. Try to get instance id from request parameter (post, get, cookie)
        if (isset($_REQUEST['i_id']))
        {
            $this->i_id = $_REQUEST['i_id'];
        }
        else if (isset($_GET['i_id']))
        {
            $this->i_id = $_GET['i_id'];
        }
        else if (isset($_POST['i_id']))
        {
            $this->i_id = $_POST['i_id'];
        }



    }

    /**
     * Returns referrers personal data
     *
     * @return array Personal user information
     */
    public function getInfo()
    {
        // Try to get data from app database
        /*if ( isset($this->info['auth_uid']) && $this->info['auth_uid'] && $this->db ) {

            // Get social media ids
            $sql = "SELECT * FROM mod_auth_user WHERE uid = :auth_uid LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':auth_uid', $this->info['auth_uid'], PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0)
            {
                $result = $stmt->fetchObject();
                if (isset($result->fb_id))
                {
                    $this->info['fb_user_id'] = $result->fb_id;
                }
                if (isset($result->lastname))
                {
                    $this->info['tw_user_id'] = $result->tw_id;
                }
                if (isset($result->email))
                {
                    $this->info['gp_user_id'] = $result->gp_id;
                }
            }

            // Get personal user data
            $sql = "SELECT * FROM mod_auth_user_data WHERE auth_uid = :auth_uid LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':auth_uid', $this->info['auth_uid'], PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0)
            {
                $result = $stmt->fetchObject();
                if (isset($result->firstname))
                {
                    $this->info['first_name'] = $result->firstname;
                }
                if (isset($result->lastname))
                {
                    $this->info['last_name'] = $result->lastname;
                }
                if (isset($result->email))
                {
                    $this->info['email'] = $result->email;
                }
            }
        }*/

        return $this->info;
    }

    /**
     * Tries to get more information about the referrers app settings
     */
    public function getApp(){
        // Get referrers app language
        if ( isset($_REQUEST['ref_app_lang']) ) {
            $this->app['lang'] = $_REQUEST['ref_app_lang'];
        }

        // Get referrers app environment
        if ( isset($_REQUEST['ref_app_env']) ) {
            $this->app['env'] = $_REQUEST['ref_app_env'];
        }
    }

    /**
     * Returns the whole referrer object
     */
    public function getReferrer()
    {
        $referrer = array(
            "app"       => $this->app,
            "info"      => $this->getInfo(),
            "i_id"      => $this->i_id
        );

        return $referrer;
    }



    /**
     * Returns the instance id of the referral source if available
     *
     * @return bool|int Instance Id of the referral source or false if not available
     */
    public function getInstanceId() {
        return $this->i_id;
    }

    /**
     * Get available data for the most current request
     *
     * @param String $request_ids    Comma seperated list of facebook request ids
     * @param String $db_table_name  Database table name of the fb requests
     * @param String $db_request_col Column name of the request id
     * @return object Returns the complete database row for the submitted most current fb_request_id
     */
    public function decodeFbRequestId(
        $request_ids,
        $db_table_name = "mod_facebook_friends",
        $db_request_col = "request_id"
    ) {
        $fb_request_id = explode(",", $request_ids);

        // check if more than one ID exists
        if (is_array($fb_request_id) == true)
        {
            $fb_request_id = array_pop($fb_request_id); // the most recent one is the last one
        }

        // Get instance id and
        $sql = "SELECT * FROM " . $db_table_name .  " WHERE " . $db_request_col . " = :request_id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':request_id', $fb_request_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0)
        {
            $result = $stmt->fetchObject();
            if (isset($result->i_id))
            {
                $this->i_id = $result->i_id;
            }
            if (isset($result->auth_uid))
            {
                $this->info['auth_uid'] = $result->auth_uid;
            }

            return $result;
        }

        return false;
    }
}
