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
	 * Template constructor.
	 *
	 * @param Api $api
	 */
	public function __construct(Api $api) {
		$this->type = 'template';

		parent::__construct($api);
	}
}