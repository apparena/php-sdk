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


	/** @var  MobileDetect */
	private $mobileDetect;

	/**
	 * Device constructor.
	 *
	 * @param AbstractEntity $entity
	 */
	public function __construct( AbstractEntity $entity ) {

		parent::__construct($entity);

		$device = [];

		if ( ! $this->mobileDetect ) {
			$this->mobileDetect = new MobileDetect();
		}

		// Get device type
		$device['type'] = 'desktop';
		if ( $this->mobileDetect->isMobile() ) {
			$device['type'] = 'mobile';
		}
		if ( $this->mobileDetect->isTablet() ) {
			$device['type'] = 'tablet';
		}

		// If device-type is submitted via GET-Parameter
		if ( isset( $_GET['device'] ) && in_array( $_GET['device'], [ 'mobile', 'tablet', 'desktop' ] ) ) {
			$device['type'] = $_GET['device'];
		}

		$this->device = $device;

	}
}