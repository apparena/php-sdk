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
	private $type;

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
		$this->type = 'desktop';
		if ( $this->mobileDetect->isMobile() ) {
			$this->type = 'mobile';
		}
		if ( $this->mobileDetect->isTablet() ) {
			$this->type = 'tablet';
		}

		// If device-type is submitted via GET-Parameter
		if ( isset( $_GET['device'] ) && in_array( $_GET['device'], [ 'mobile', 'tablet', 'desktop' ] ) ) {
			$this->type = $_GET['device'];
		}

	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}


	public function toArray() {
		return [
			'type' => $this->getType(),
		];
	}


}