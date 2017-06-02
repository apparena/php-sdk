<?php

namespace AppArena\Models;

use AppArena\Models\Entities\AbstractEntity;
use AppArena\Models\Environment\AbstractEnvironment;
use AppArena\Models\Environment\Browser;
use AppArena\Models\Environment\Device;
use AppArena\Models\Environment\Domain;
use AppArena\Models\Environment\Facebook;
use AppArena\Models\Environment\OperatingSystem;
use AppArena\Models\Environment\Website;

/**
 * Everything related to the current environment (device, browser, context (website, embed or facebook fanpage)
 *
 * Class Environment
 * @package AppArena\Models
 */
class Environment {

	/** @var  Browser */
	protected $browser;
	/** @var  Device */
	protected $device;
	/** @var Facebook */
	protected $facebook;
	/** @var Website */
	protected $website;
	/** @var Domain */
	protected $domain;

	/**
	 * Environment constructor.
	 * Initialize all available environments
	 */
	public function __construct( AbstractEntity $entity ) {

		// Initialize the environment information
		$this->facebook = new Facebook( $entity );
		$this->website  = new Website( $entity );
		$this->domain   = new Domain( $entity );

		// Initialize User environment
		$this->browser = new Browser( $entity );
		$this->device  = new Device( $entity );

	}

	/**
	 * Evaludates the priority and compatibility of each environment (Domain, Facebook, Website) and returns the most
	 * important one.
	 *
	 * @return AbstractEnvironment
	 */
	public function getPrimaryEnvironment() {

		if ($this->website->getUrl()) {
			return $this->website;
		}

		if ($this->facebook->getPageId()) {
			return $this->facebook;
		}

		return $this->domain;
	}


	/**
	 * @return Browser
	 */
	public function getBrowser() {
		return $this->browser;
	}

	/**
	 * @return Device
	 */
	public function getDevice() {
		return $this->device;
	}

	/**
	 * @return Facebook
	 */
	public function getFacebook() {
		return $this->facebook;
	}

	/**
	 * @return Website
	 */
	public function getWebsite() {
		return $this->website;
	}

}