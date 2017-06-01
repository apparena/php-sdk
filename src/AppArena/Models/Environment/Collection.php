<?php
/**
 * Created by PhpStorm.
 * User: s.buckpesch
 * Date: 23.05.2017
 * Time: 14:08
 */

namespace AppArena\Models\Environment;


class Collection extends \ArrayObject {

	/** @var  AbstractEnvironment */
	protected $primaryEnvironment;


	/**
	 * @return AbstractEnvironment
	 */
	public function getPrimaryEnvironment() {
		return $this->primaryEnvironment;
	}



}