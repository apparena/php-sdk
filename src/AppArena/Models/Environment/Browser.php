<?php

namespace AppArena\Models\Environment;
use AppArena\Models\Entities\AbstractEntity;
use phpbrowscap\Browscap;

/**
 * All functionality related to the users browser
 * Class Browser
 * @package AppArena\Models
 */
class Browser extends AbstractEnvironment {

	private $name;

	/**
	 * Browser constructor.
	 *
	 * @param AbstractEntity $entity
	 */
	public function __construct( AbstractEntity $entity ) {
		parent::__construct( $entity );

		if ( ! $this->browscap ) {
			$this->browscap = new Browscap( realpath( dirname( __FILE__ ) ) . "/../../../asset" );
			// Use Normal instead of Full browscap.ini to save memory-usage
			$this->browscap->remoteIniUrl = 'http://browscap.org/stream?q=PHP_BrowsCapINI';
		}

		// Get information about the current browser's user agent
		$browser = $this->browscap->getBrowser( null, true );
		$this->browser = array(
			'ua'      => $browser['browser_name'],
			'name'    => $browser['Browser'],
			'version' => $browser['MajorVer']
		);

	}

	/**
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns all relevant Environment information as array
	 */
	public function toArray() {
		return [
			'name'     => $this->getName(),
		];
	}
}