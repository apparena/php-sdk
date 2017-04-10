<?php

namespace AppArena\Models;

/**
 * A version of an App-Arena project
 * Class Version
 * @package AppArena\Models
 */
class Version extends AbstractEntity {

	/**
	 * @inheritdoc
	 */
	public function __construct( $id ) {
		$this->type = 'version';

		parent::__construct( $id );
	}
}