<?php
/**
 * App-Manager (http://app-arena.readthedocs.org/en/latest/)
 *
 * @link      http://app-arena.readthedocs.org/en/latest/ for complete API and developer documentation
 * @copyright App-Arena.com - iConsultants GmbH (http://www.app-arena.com)
 * @license   -
 */

namespace AppManager\API;

use AppManager\Helper\Cache;

/**
 * Class Api App-Arena App-Manager API object responsible for the communication with the App-Manager REST API
 * @package AppManager\API
 */
class Api
{
    protected $base_url = 'https://v2.app-arena.com/api/v1/';
    //protected $base_url = 'http://appmanager2.frd.info/api/v1/';
    protected $error_msg = ''; // Error message on failed soap call
    protected $cache = false;
    private $cache_reset = false;
    private $lang; // Language

    protected $auth_username = '';
    protected $auth_password = '';


    /**
     * @param array $params Parameter to control the initialization
     *                      bool 'cache_reset' Reset the cache on initialization?
     */
    function __construct($params = array())
    {

        // Initialize Cache object
        if (isset($params['cache_dir']))
        {
            $cache_dir = $params['cache_dir'];
        }
        else
        {
            $cache_dir = false;
        }
        $this->cache = new Cache(
            array(
                'cache_dir' => $cache_dir
            )
        );

    }


    /**
     * Returns the data of the requested route as array.
     * @param string $route  Requested route
     * @param array  $params Additional paramater for the request
     * @return array API response
     */
    function get($route, $params = array())
    {
        if ($this->lang)
        {
            $cache_key = str_replace('/', '_', $route) . "_" . $this->lang . "_" . md5($route . json_encode($params));
        }
        else
        {
            $cache_key = str_replace('/', '_', $route) . "_" . md5($route . json_encode($params));
        }

        if (!$this->cache_reset && $this->cache->exists($cache_key))
        {
            $response = $this->cache->load($cache_key);
        }
        else
        {
            $response = $this->_get($route, $params);
            if ($response != false)
            {
                $this->cache->save($cache_key, $response);
            }
        }

        $response = json_decode($response, true);

        if ($response == false)
        {
            return false;
        }

        if (isset($response['status']))
        {
            return false;
        }

        return $response;
    }


    protected function _get($path, $params = array())
    {
        $url = $this->base_url . $path;

        if ($params != false)
        {
            $url .= "?" . http_build_query($params);
        }

        $username = $this->auth_username;
        $password = $this->auth_password;
        $ch       = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        /*$headers = array(
          'Content-Type:application/json',
          'Authorization: Basic '. base64_encode("$username:$password") // <---
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);*/
        $out = curl_exec($ch);

        if ($out == false)
        {
            $error = curl_error($ch);
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //var_dump($httpcode);exit();
        curl_close($ch);

        return $out;
    }

    /**
     * @param mixed $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }




}
