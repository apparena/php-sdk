<?php
/**
 * Created by PhpStorm.
 * User: s.buckpesch
 * Date: 07.04.2017
 * Time: 15:32
 */

namespace AppArena\Models;


class Template extends AbstractEntity {

	/**
	 * @inheritdoc
	 */
	public function __construct( $id ) {
		$this->type = 'template';

		parent::__construct( $id );
	}
}