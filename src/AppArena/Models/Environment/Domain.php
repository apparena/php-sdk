<?php
namespace AppArena\Models\Environment;

use AppArena\Models\Entities\AbstractEntity;

/**
 * Direct access to the web app using a certain domain
 * Class Facebook
 * @package AppArena\Models
 */
class Domain extends AbstractEnvironment {


	/**
	 * Facebook constructor.
	 *
	 * @param AbstractEntity $entity
	 */
	public function __construct(AbstractEntity $entity) {

		$this->priority = 10;

		

	}
}