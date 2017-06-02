<?php

namespace AppArena\Models\Environment;

use AppArena\Models\Entities\AbstractEntity;

/**
 * Direct access to the web app using a certain domain
 * Class Facebook
 * @package AppArena\Models
 */
class Domain extends AbstractEnvironment {

	private $url;


	/**
	 * Facebook constructor.
	 *
	 * @param AbstractEntity $entity
	 */
	public function __construct( AbstractEntity $entity ) {
		parent::__construct( $entity );
		$this->type     = 'domain';
		$this->priority = 10;
	}

	/**
	 * @return mixed
	 */
	public function getUrl() {
		return $this->url;
	}

}