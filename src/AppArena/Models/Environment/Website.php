<?php

namespace AppArena\Models\Environment;

use AppArena\Models\Entities\AbstractEntity;

/**
 * All functionality related to website embedded iframes
 * Class Facebook
 * @package AppArena\Models
 */
class Website extends AbstractEnvironment {


	private $url;

	/**
	 * Facebook constructor.
	 *
	 * @param AbstractEntity $entity
	 */
	public function __construct( AbstractEntity $entity ) {

		parent::__construct( $entity );
		$this->type     = 'website';
		$this->priority = 20;
		$website        = false;

		// Try to get the website Url from the URL
		if ( isset( $_GET['website'] ) ) {
			$website = $_GET['website'];
		}

		// Try to get the website from the cookie
		if ( ! $website && $this->getCookieValue( 'website' ) ) {
			$website = $this->getCookieValue( 'website' );
		}

		$this->url = $website;
	}

	/**
	 * @return bool|mixed
	 */
	public function getUrl() {
		return $this->url;
	}


}