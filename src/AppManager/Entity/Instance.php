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
    protected $lang = "de_DE";
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
        if (isset($params['m_id']) && $params['m_id']) {
            $this->m_id = $params['m_id'];
        }

        // Initialize Instance ID
        if (isset($params['i_id']) && $params['i_id']) {
            $this->id = $params['i_id'];
        } else {
            $this->recoverId();
        }

        // Initialize Language
        if (isset($params['lang'])) {
            $this->lang = $params['lang'];
        } else {
            $this->recoverLangTag();
        }

    }

    /**
     * @return array
     */
    public function getInfos()
    {
        // Update the language for the current request
        $this->api->setLang($this->getLang());
        $response = $this->api->get("instances/" . $this->id);
        if ($response == false) {
            return false;
        }
        $this->info = $response;

        return $this->info;
    }

    function getConfigs()
    {
        // Update the language for the current request
        $this->api->setLang($this->getLang());
        $response = $this->api->get("instances/$this->id/configs", array('page_size' => 10000));

        if ($response == false) {
            return false;
        }
        $data = $response['_embedded']['data'];

        $config = array();
        foreach ($data as $v) {
            $config[$v['id']] = $v;
        }
        $this->config = $config;

        return $this->config;
    }

    function getTranslations()
    {
        $lang = $this->getLang();
        $this->api->setLang($lang);
        $response = $this->api->get(
            "instances/$this->id/languages/$lang/translations",
            array('page_size' => 10000)
        );
        if ($response == false) {
            return false;
        }

        $data = $response['_embedded']['data'];

        $translation = array();
        foreach ($data as $v) {
            $translation[$v['translation_id']] = $v['value'];
        }
        $this->translation = $translation;

        return $this->translation;
    }

    /**
     * Tries to get the instance ID from the current environment (e.g. Cookies, Facebook, Request-Parameters)
     * @params array $params Additional information helping to descover the instance ID
     */
    private function recoverId()
    {
        $id = false;

        // Try to get the ID from the REQUEST
        if (isset($_REQUEST['i_id'])) {
            $id = $_REQUEST['i_id'];
        } else {
            if (isset($_SERVER['i_id'])) {
                $id = $_SERVER['i_id'];
            } else {
                // Try to get the ID from the facebook fanpage tab and m_id (app model)
                $id = $this->getIdFromFBRequest();

                if (!$id) {
                    // Try to get the ID from a cookie
                    if (isset($_COOKIE['aa_i_id'])) {
                        $id = $_COOKIE['aa_i_id'];
                    } else {
                        // Try to get the ID from the user session
                        if (!empty($_SESSION['current_i_id'])) {
                            $id = $_SESSION["current_i_id"];
                        }
                    }
                }
            }
        }

        // Set ID to the object and the users session and cookie
        if ($id) {
            $_SESSION['current_i_id'] = intval($id);
            $this->id                 = intval($id);
        }

        return $this->id;
    }


    /**
     * Tries to get the Language settings from the current environment (e.g. Cookies, Request-Parameters, Facebook)
     */
    private function recoverLangTag()
    {

        $lang = false;
        if (isset($_GET['lang'])) {
            $lang = $_GET['lang'];
        } else {
            if (isset($_GET['locale'])) {
                $lang = $_GET['locale'];
            } else {
                if (isset($app_data) && isset($app_data['locale'])) {
                    $lang = $app_data['locale'];
                } else {
                    if (isset($_COOKIE['aa_' . $this->id . '_lang'])) {
                        $lang = $_COOKIE['aa_' . $this->id . '_lang'];
                    }
                }
            }
        }

        if ($lang) {
            $this->setLang($lang);
        }

        return $this->lang;
    }

    /**
     * Returns and sets the instance_id by requesting the API for data
     */
    private function getIdFromFBRequest()
    {
        $app_data   = array();
        $fb_page_id = false;
        $i_id       = false;

        if (isset($_REQUEST['signed_request'])) {
            list($encoded_sig, $payload) = explode('.', $_REQUEST['signed_request'], 2);
            $signed_request = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
            if (isset($signed_request['app_data'])) {
                $app_data = json_decode($signed_request['app_data'], true);
            }

            if (isset($signed_request['page']['id']) && $signed_request['page']['id']) {
                $fb_page_id = $signed_request['page']['id'];
            }

            if ($fb_page_id && $this->m_id) {
                $request_url = "https://manager.app-arena.com/api/v1/env/fb/pages/" . $fb_page_id .
                               "/instances.json?m_id=" . $this->m_id . "&active=true";
                $instances   = json_decode(file_get_contents($request_url), true);
                foreach ($instances['data'] as $instance) {
                    if ($instance['activate'] == 1) {
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
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @param string $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    /**
     * @return boolean
     */
    public function getId()
    {
        return intval($this->id);
    }


    /**
     * Returns the value of a config value
     * @param String       $config_id Config identifier to get the data for
     * @param String|array $attr      Attribute or Attributes which should be returned
     * @return mixed Requested config value
     */
    function getConfig($config_id, $attr = null)
    {
        $config = $this->getConfigs();
        $args   = func_get_args();
        $num    = func_num_args();

        if ($num == 0) {
            return '';
        }

        $config_id = $args[0];
        if ($num == 1) {
            if (isset($config[$config_id]['value'])) {
                return $config[$config_id]['value'];
            }
        }

        // Return certain attributes of a config value
        if ($num == 2) {
            $attributes = $args[1];
            if (isset($config[$config_id]) && is_array($attributes)) {
                $result = array($attributes);
                foreach ($config[$config_id] as $attribute => $value) {
                    if (isset($result[$attribute])) {
                        $result[$attribute] = $value;
                    } else {
                        $result[$attribute] = null;
                    }

                    return $result;
                }
            }

            if (isset($config[$config_id][$attributes])) {
                return $config[$config_id][$attributes];
            }

        }

        return false;
    }


    /**
     * Returns one translation
     * @return mixed
     */
    function getTranslation()
    {
        $translate = $this->getTranslations();

        $args = func_get_args();
        $num  = func_num_args();

        if ($num == 0) {
            return '';
        }

        $str = $args[0];
        if ($num == 1) {
            return $translate->_($str);
        }

        unset($args[0]);
        $args  = str_replace('"', '\"', $args);
        $param = '"' . implode('","', $args) . '"';

        $str = '$ret=sprintf("' . $translate->_($str) . '",' . $param . ');';
        eval($str);

        return $ret;
    }

    /**
     * @return boolean
     */
    public function getMId()
    {
        if ($this->m_id) {
            return $this->m_id;
        }

        $this->m_id = $this->getInfo("m_id");

        return $this->m_id;
    }

    /**
     * Returns only the requested info attribute
     * @param $key Key of the attribute
     * @return String Value of the requested attribute
     */
    public function getInfo($key)
    {
        $infos = $this->getInfos();

        if (isset($infos[$key])) {
            return $infos[$key];
        }

        return false;
    }

}