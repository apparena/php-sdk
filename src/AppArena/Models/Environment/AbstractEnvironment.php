<?php
/**
 * Created by PhpStorm.
 * User: s.buckpesch
 * Date: 11.04.2017
 * Time: 10:43
 */

namespace AppArena\Models\Environment;

use AppArena\AppManager;
use AppArena\Models\Entities\AbstractEntity;

abstract class AbstractEnvironment implements EnvironmentInterface {

	/** @var  int The environment priority. The higher the priority, the more likely is a redirect to that environment */
	protected $priority;
	/** @var AbstractEntity */
	protected $entity;
	protected $type;

	protected $cookie_key;

	/**
	 * Abstract constructor.
	 *
	 * @param AbstractEntity $entity
	 */
	public function __construct( AbstractEntity $entity ) {
		$this->entity     = $entity;
		$this->cookie_key = AppManager::COOKIE_KEY . $this->entity->getId();
	}

	/**
	 * Returns all relevant Environment information as array
	 */
	public function toArray() {
		return [
			'priority' => $this->getPriority(),
			'type'     => $this->getType(),
		];
	}

	/**
	 * @return int
	 */
	public function getPriority() {
		return $this->priority;
	}


	/**
	 * Returns a value from the SmartCookie
	 *
	 * @param String $key to search in the SmartCookie
	 *
	 * @return mixed Value corresponding to the key
	 */
	protected function getCookieValue( $key ) {
		if ( isset( $_COOKIE[ $this->cookie_key ] ) ) {
			// Decode cookie value
			$cookie         = $_COOKIE[ $this->cookie_key ];
			$cookie_decoded = json_decode( $cookie, true );
			if ( isset( $cookie_decoded[ $key ] ) ) {
				return $cookie_decoded[ $key ];
			}
		}

		return false;

	}

	/**
	 * @return mixed
	 */
	public function getType() {
		return $this->type;
	}

}