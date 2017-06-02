<?php
/**
 * Created by PhpStorm.
 * User: s.buckpesch
 * Date: 11.04.2017
 * Time: 10:42
 */

namespace AppArena\Models\Environment;


interface EnvironmentInterface {

	/**
	 * Returns an array with all relevant information of the environment
	 * @return array
	 */
	public function toArray();

}