<?php

namespace AppArena\Models\Environment;
use AppArena\Models\Entities\AbstractEntity;
use UserAgentParser\Model\OperatingSystem;
use UserAgentParser\Model\UserAgent;
use UserAgentParser\Provider\WhichBrowser;

/**
 * All functionality related to the users browser
 * Class Browser
 * @package AppArena\Models
 */
class Browser extends AbstractEnvironment {

	/** @var UserAgent */
	private $ua;

	/**
	 * Browser constructor.
	 *
	 * @param AbstractEntity $entity
	 */
	public function __construct( AbstractEntity $entity ) {
		parent::__construct( $entity );
		$userAgent = false;
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$userAgent = $_SERVER['HTTP_USER_AGENT'];
		}
		try {
			$provider = new WhichBrowser();
			/* @var $result \UserAgentParser\Model\UserAgent */
			$this->ua = $provider->parse($userAgent);
		} catch (\Exception $e) {
			$this->ua = $userAgent;
		}

		// optional add all headers, to improve the result further
		//$this->ua = $provider->parse($userAgent, getallheaders());
	}

	/**
	 * @return mixed
	 */
	public function getName() {
		return $this->ua->getBrowser()->getName();
	}

	/**
	 * Returns all relevant Environment information as array
	 * @return array
	 */
	public function toArray() {

		$result = $this->ua->toArray();
		$result = array_merge_recursive($result, [
			'ua' => $_SERVER['HTTP_USER_AGENT'],
			'name'     => $this->ua->getBrowser()->getName(),
			'version' => $this->ua->getBrowser()->getVersion()
		]);

		return $result;
	}

	/**
	 * @return \UserAgentParser\Model\Device
	 */
	public function getDevice() {
		if (!$this->getUa()) {
			return new \UserAgentParser\Model\Device();
		}

		return $this->getUa()->getDevice();
	}

	/**
	 * @return \UserAgentParser\Model\OperatingSystem
	 */
	public function getOperatingSystem() {
		if (!$this->getUa()) {
			return new OperatingSystem();
		}

		return $this->getUa()->getOperatingSystem();
	}

	/**
	 * @return \UserAgentParser\Model\Browser
	 */
	public function getBrowser() {
		if (!$this->getUa()) {
			return new \UserAgentParser\Model\Browser();
		}

		return $this->getUa()->getBrowser();
	}

	/**
	 * @return UserAgent|false
	 */
	public function getUa() {
		return $this->ua;
	}

}