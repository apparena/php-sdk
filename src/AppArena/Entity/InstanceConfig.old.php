<?php
/**
 * App-Manager (http://app-arena.readthedocs.org/en/latest/)
 *
 * @link      http://app-arena.readthedocs.org/en/latest/ for complete API and developer documentation
 * @copyright App-Arena.com - iConsultants GmbH (http://www.app-arena.com)
 * @license   2015 -
 */

namespace AppArena\Entity;

use AppArena\API\Api;

class InstanceConfig
{
    function __construct($data)
    {
        $this->setData($data);
    }

    //2d array
    protected $data = array();

    function __get($k)
    {
        return $this->get($k, "value");
    }

    function __isset($k)
    {
        return ($this->get($k, "value") !== null);
    }

    function get($k, $attr = "value")
    {
        if (isset($this->data[$k]) == false) {
            return null;
        }

        return $this->data[$k][$attr];
    }

    function set($k, $attr = "value", $value)
    {
        if (isset($this->data[$k]) == false) {
            $this->data[$k] = array();
        }

        $this->data[$k][$attr] = $value;
    }

    function setData($data)
    {
        if (is_array($data)) {
            $this->data = $data;
        }
    }

    function getData()
    {
        return $this->data;
    }
    
}
