<?php

namespace AppArena\Models\Environment;
use AppArena\Models\Entities\AbstractEntity;
use UserAgentParser\Model\UserAgent;
use UserAgentParser\Provider\PiwikDeviceDetector;

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

		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		$provider = new PiwikDeviceDetector();

		/* @var $result \UserAgentParser\Model\UserAgent */
		//$result = $provider->parse($userAgent);
		// optional add all headers, to improve the result further
		$this->ua = $provider->parse($userAgent, getallheaders());
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
}