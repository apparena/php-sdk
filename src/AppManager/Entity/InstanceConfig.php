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

class InstanceConfig 
{
  function __construct($data)
  {
    $this->setData($data);
  }

   //2d array
   protected $data=array();

   function __get($k)
   {
      return $this->get($k,"value");
   }

   function __isset($k)
   {
      return ($this->get($k,"value") !== null);
   }

   function get($k,$attr="value")
   {
      if(isset($this->data[$k]) == false)
      {
         return null;
      }

      return $this->data[$k][$attr];
   }

   function set($k,$attr="value",$value)
   {
      if(isset($this->data[$k]) == false)
      {
        $this->data[$k]=array();
      }

      $this->data[$k][$attr]=$value;
   }




   function setData($data)
   {
      if(is_array($data))
      {
         $this->data=$data;
      }
   }

   function getData()
   {
      return $this->data;
   }

   //custom methods
   function getVoteMode()
   {
      $mode=$this->get("app_vote_mode");

      $map=array(
         'count'=>"item",
         'stars'=>"item",
         'cat-star-rating'=>"category",
         'cat-vote-count'=>"category",
      );

      if(isset($map[$mode])) return $map[$mode];
      else return "item";
   }

   function getVoteType()
   {
      $mode=$this->get("app_vote_mode");

      $map=array(
         'count'=>"count",
         'stars'=>"star",
         'cat-star-rating'=>"star",
         'cat-vote-count'=>"count",
      );

      if(isset($map[$mode])) return $map[$mode];
      else return "count";
   }
}
