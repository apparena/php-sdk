<?php
/**
 * Created by PhpStorm.
 * User: s.buckpesch
 * Date: 11.04.2017
 * Time: 10:43
 */

namespace AppArena\Models\Environment;


abstract class AbstractEnvironment implements EnvironmentInterface {

	/** @var  int The environment priority. The higher the priority, the more likely is a redirect to that environment */
	protected $priority;

	/**
	 * @return int
	 */
	public function getPriority() {
		return $this->priority;
	}





}