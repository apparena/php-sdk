<?php
namespace AppArena\Models\Environment;

/**
 * All functionality related to the users Operating System
 * Class OperatingSystem
 * @package AppArena\Models
 */
class OperatingSystem extends AbstractEnvironment {


	/**
	 * OperatingSystem constructor.
	 *
	 * @param AbstractEntity $entity
	 */
	public function __construct( AbstractEntity $entity ) {

		parent::__construct($entity);



	}
}