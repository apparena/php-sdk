<?php

namespace AppArena\Models;

/**
 * A version of an App-Arena project
 * Class Version
 * @package AppArena\Models
 */
class Version extends AbstractEntity {

	/**
	 * Version constructor.
	 *
	 * @param Api $api
	 */
	public function __construct( int $id, Api $api ) {
		$this->type = 'version';

		parent::__construct($api);
	}
}