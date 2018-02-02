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
		} else {
			// ... from the App Channels
			$channels = $this->entity->getChannels();
			if ( is_array( $channels ) ) {
				foreach ( $channels as $channel ) {
					if ( $channel['type'] === 'website' ) {
						$website = $channel['url'];
						break;
					}
				}
			}
		}

		$this->url = $website;
	}

	/**
	 * Returns all relevant Environment information as array
	 */
	public function toArray() {
		return [
			'priority' => $this->getPriority(),
			'type'     => $this->getType(),
			'website'  => $this->getUrl(),
		];
	}

	/**
	 * @return bool|mixed
	 */
	public function getUrl() {

		if ( filter_var( $this->url, FILTER_VALIDATE_URL ) === false ) {
			return false;
		}

		return $this->url;
	}


}