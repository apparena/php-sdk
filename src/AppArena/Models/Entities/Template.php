<?php
namespace AppArena\Models\Entities;


class Template extends AbstractEntity {

	/**
	 * @inheritdoc
	 */
	public function __construct( $id ) {
		$this->type = 'template';

		parent::__construct( $id );
	}
}