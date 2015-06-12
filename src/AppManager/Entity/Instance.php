<?php
/**
 * App-Manager (http://app-arena.readthedocs.org/en/latest/)
 *
 * @link      http://app-arena.readthedocs.org/en/latest/ for complete API and developer documentation
 * @copyright App-Arena.com - iConsultants GmbH (http://www.app-arena.com)
 * @license   2015 -
 */

namespace AppManager\Entity;

use AppManager\API\Api;

/**
 * Class Instance Instance object
 */
class Instance
{

    protected $id = false; // Default instance ID (Demo), which should be overwritten...
    protected $m_id = false; // ID of this instances app model
    protected $template_id; // ID of this instances template
    protected $config = array();
    protected $translation = array();
    protected $info = array();
    protected $lang_tag = "de_DE";
    private $cache_dir = false;

    /**
     * Initialize the Instance object
     * @params AppManager\API\Api $api API object to submit requests
     * @params array $params Parameter to initialize the instance object
     */
    function __construct(Api $api, $params = array())
    {
        $this->api = $api;

        // Initialize Instance ID
        if (isset($params['m_id']) && $params['m_id'])
        {
            $this->m_id = $params['m_id'];
        }

        // Initialize Instance ID
        if (isset($params['i_id']) && $params['i_id'])
        {
            $this->id = $params['i_id'];
        }
        else
        {
            $this->recoverId();
        }

        // Initialize Language
        if (isset($params['lang_tag']))
        {
            $this->lang_tag = $params['lang_tag'];
        }
        else
        {
            $this->recoverLangTag();
        }

    }

    /**
     * @return array
     */
    public function getInfo()
    {
        $response = $this->api->get("instances/" . $this->id);
        if ($response == false)
        {
            return false;
        }
        $this->info = $response;

        return $this->info;
    }

    /**
     * @param array $info
     */
    public function setInfo($info)
    {
        $this->info = $info;
    }

    function setConfigs($config)
    {
        $this->config = $config;
    }

    function getConfigs()
    {
        $response = $this->api->get("instances/$this->id/configs", array('page_size' => 10000));

        if ($response == false)
        {
            return false;
        }
        $data = $response['_embedded']['data'];

        $config = array();
        foreach ($data as $v)
        {
            $config[$v['id']] = $v;
        }
        $this->config = $config;

        return $this->config;
    }

    function getTranslations()
    {
        $lang_tag = $this->getLangTag();
        $response = $this->api->get(
            "instances/$this->id/languages/$lang_tag/translations",
            array('page_size' => 10000)
        );
        if ($response == false)
        {
            return false;
        }

        $data = $response['_embedded']['data'];

        $translation = array();
        foreach ($data as $v)
        {
            $translation[$v['translation_id']] = $v['value'];
        }
        $this->translation = $translation;

        return $this->translation;
    }

    function setTranslations($translation)
    {
        $this->translation = $translation;
    }

    /**
     * Tries to get the instance ID from the current environment (e.g. Cookies, Facebook, Request-Parameters)
     * @params array $params Additional information helping to descover the instance ID
     */
    private function recoverId()
    {
        $id = false;

        // Try to get the ID from the REQUEST
        if (isset($_REQUEST['i_id']))
        {
            $id = $_REQUEST['i_id'];
        }
        else
        {
            if (isset($_SERVER['i_id']))
            {
                $id = $_SERVER['i_id'];
            }
            else
            {
                // Try to get the ID from a cookie
                if (isset($_COOKIE['aa_i_id']))
                {
                    $id = $_COOKIE['aa_i_id'];
                }
                else
                {
                    // Try to get the ID from the user session
                    if (!empty($_SESSION['current_i_id']))
                    {
                        $id = $_SESSION["current_i_id"];
                    } else {
                        // Try to get the ID from the facebook fanpage tab and m_id (app model)
                        $id = $this->getIdFromFBRequest();
                    }
                }
            }
        }

        // Set ID to the object and the users session and cookie
        if ($id) {
            $_SESSION['current_i_id'] = $id;
            $this->id = $id;
        }

        return $this->id;
    }


    /**
     * Tries to get the Language settings from the current environment (e.g. Cookies, Request-Parameters, Facebook)
     */
    private function recoverLangTag()
    {

        $lang_tag = false;
        if (isset($_GET['lang_tag']))
        {
            $lang_tag = $_GET['lang_tag'];
        }
        else
        {
            if (isset($_GET['locale']))
            {
                $lang_tag = $_GET['locale'];
            }
            else
            {
                if (isset($app_data) && isset($app_data['locale']))
                {
                    $lang_tag = $app_data['locale'];
                }
                else
                {
                    if (isset($_COOKIE['aa_' . $this->id . '_lang_tag']))
                    {
                        $lang_tag = $_COOKIE['aa_' . $this->id . '_lang_tag'];
                    }
                }
            }
        }

        if ($lang_tag)
        {
            $this->lang_tag = $lang_tag;
        }

        return $this->lang_tag;
    }

    /**
     * Returns and sets the instance_id by requesting the API for data
     */
    private function getIdFromFBRequest()
    {
        $app_data = array();
        $fb_page_id = false;
        $i_id = false;

        if (isset($_REQUEST['signed_request']))
        {
            list($encoded_sig, $payload) = explode('.', $_REQUEST['signed_request'], 2);
            $signed_request = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
            if (isset($signed_request['app_data']))
            {
                $app_data = json_decode($signed_request['app_data'], true);
            }

            if ( isset($signed_request['page']['id']) && $signed_request['page']['id'] ) {
                $fb_page_id = $signed_request['page']['id'];
            }

            if ( $fb_page_id && $this->m_id )
            {
                $request_url = "https://manager.app-arena.com/api/v1/env/fb/pages/" . $fb_page_id .
                    "/instances.json?m_id=" . $this->m_id . "&active=true";
                $instances   = json_decode(file_get_contents($request_url), true);
                foreach ($instances['data'] as $instance)
                {
                    if ($instance['activate'] == 1)
                    {
                        $i_id = $instance['i_id'];
                    }
                }
            }
        }
        return $i_id;
    }

    /**
     * @return string
     */
    public function getLangTag()
    {
        return $this->lang_tag;
    }

    /**
     * @param string $lang_tag
     */
    public function setLangTag($lang_tag)
    {
        $this->lang_tag = $lang_tag;
    }


}