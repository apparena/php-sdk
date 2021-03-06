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
	/** @var AbstractEntity */
	protected $entity;

	/**
	 * Environment constructor.
	 * Initialize all available environments
	 */
	public function __construct( AbstractEntity $entity ) {

		// Initialize the environment information
		$this->entity   = $entity;
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

		$channels   = $this->getEntity()->getChannels();
		$deviceType = $this->getDevice()->getDeviceType();

		// Check if the channel is compatible with the current device
		foreach ( $channels as $channel ) {
			// Facebook page tab cannot be accessed by mobile and desktop devices
			if ( $channel['type'] == 'facebook' && in_array( $deviceType, [ 'mobile', 'tablet' ] ) ) {
				continue;
			}

			switch ( $channel['type'] ) {
				case "website":
					return $this->getWebsite();
			        break;
				case "facebook":
					return $this->getFacebook();
					break;
			}
		}

		return $this->getDomain();
	}


	/**
	 * @return \UserAgentParser\Model\Browser
	 */
	public function getBrowser() {

		if ( $this->browser ) {
			return $this->browser->getBrowser();
		}

		return null;
	}

	/**
	 * @return \UserAgentParser\Model\OperatingSystem
	 */
	public function getOperationSystem() {
		if ( $this->browser ) {
			return $this->browser->getOperatingSystem();
		}

		return null;
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

	/**
	 * @return AbstractEntity
	 */
	public function getEntity(): AbstractEntity {
		return $this->entity;
	}

	/**
	 * @return Domain
	 */
	public function getDomain(): Domain {
		return $this->domain;
	}



}