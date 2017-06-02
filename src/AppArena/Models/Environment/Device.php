<?php
namespace AppArena\Models\Environment;
use AppArena\Models\Entities\AbstractEntity;
use Detection\MobileDetect;

/**
 * All functionality related to the users device
 * Class Facebook
 * @package AppArena\Models
 */
class Device extends AbstractEnvironment {

	/** @var  string */
	protected $deviceType;

	/** @var  MobileDetect */
	private $mobileDetect;

	/**
	 * Device constructor.
	 *
	 * @param AbstractEntity $entity
	 */
	public function __construct( AbstractEntity $entity ) {

		parent::__construct($entity);

		if ( ! $this->mobileDetect ) {
			$this->mobileDetect = new MobileDetect();
		}

		// Get device type
		$this->deviceType = 'desktop';
		if ( $this->mobileDetect->isMobile() ) {
			$this->deviceType = 'mobile';
		}
		if ( $this->mobileDetect->isTablet() ) {
			$this->deviceType = 'tablet';
		}

		// If device-type is submitted via GET-Parameter
		if ( isset( $_GET['device'] ) && in_array( $_GET['device'], [ 'mobile', 'tablet', 'desktop' ] ) ) {
			$this->deviceType = $_GET['device'];
		}

	}

	/**
	 * @return string
	 */
	public function getDeviceType() {
		return $this->deviceType;
	}


	public function toArray() {
		return [
			'deviceType' => $this->getDeviceType(),
		];
	}


}