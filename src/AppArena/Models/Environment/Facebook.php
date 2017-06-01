<?php
namespace AppArena\Models\Environment;
use AppArena\Models\Entities\AbstractEntity;

/**
 * All functionality related to the Facebook Page Tab environment
 * Class Facebook
 * @package AppArena\Models
 */
class Facebook extends AbstractEnvironment  {

	private $appId;
	private $pageId;
	private $pageUrl;
	private $pageTab;

	/** @var  AbstractEntity */
	protected $entity;
	private $signedRequest;

	/**
	 * Facebook constructor.
	 *
	 * @param AbstractEntity $entity
	 */
	public function __construct(AbstractEntity $entity) {

		$this->priority = 10;

		// Get Facebook page tab parameters and write them to GET parameters
		if ( isset( $_REQUEST['signed_request'] ) ) {
			$this->signedRequest = $_REQUEST['signed_request'];
			$fb_signed_request                = $this->parse_signed_request( $_REQUEST['signed_request'] );
			// So use the current Facebook page for sharing, if no fb_page_id is defined via GET
			if ( isset( $fb_signed_request['page']['id'] ) && ! isset( $_GET['fb_page_id'] ) ) {
				$_GET['fb_page_id'] = $fb_signed_request['page']['id'];
			}
			// And the parameter-passthrough mechanism, will reset all GET parameters from app_data
			if ( isset( $fb_signed_request['app_data'] ) ) {
				$params = json_decode( urldecode( $fb_signed_request['app_data'] ), true );
				foreach ( $params as $key => $value ) {
					if ( ! isset( $_GET[ $key ] ) ) {
						$_GET[ $key ] = $value;
					}
				}
			}
		} else {
			$this->signedRequest = false;
		}

		// Initialize Facebook Information ... (and check if the SmartLink should redirect to Facebook)
		$fb_page_id = false;
		$fb_app_id  = $this->entity->getInfo( 'fb_app_id' );
		if ( isset( $_GET['fb_app_id'] ) && $_GET['fb_app_id'] ) {
			$fb_app_id = $_GET['fb_app_id'];
		}
		if ( $fb_app_id ) {
			if ( isset( $_GET['fb_page_id'] ) ) {
				// ... from GET-Parameter
				$fb_page_id  = $_GET['fb_page_id'];
				$fb_page_url = "https://www.facebook.com/" . $fb_page_id . '/app/' . $fb_app_id;

				$this->appId        = $fb_app_id;
				$this->pageId       = $fb_page_id;
				$this->pageUrl      = "https://www.facebook.com/" . $fb_page_id;
				$this->pageTab      = $fb_page_url;
				$this->facebook['use_as_target'] = true;
			} else {
				$facebook = $this->getCookieValue( "facebook" );
				if ( isset( $facebook['page_id'] ) && $facebook['page_id'] && $facebook['use_as_target'] ) {
					// ... from COOKIE-Parameter
					$fb_page_id  = $facebook['page_id'];
					$fb_page_url = "https://www.facebook.com/" . $fb_page_id . '/app/' . $fb_app_id;

					$this->appId        = $fb_app_id;
					$this->pageId       = $fb_page_id;
					$this->pageUrl      = "https://www.facebook.com/" . $fb_page_id;
					$this->pageTab      = $fb_page_url;
					$this->facebook['use_as_target'] = true;
				} else {
					// ... from the Instance
					if ( $this->entity->getInfo( 'fb_page_url' ) ) {
						$fb_page_id  = $this->entity->getInfo( 'fb_page_id' );
						$fb_page_url = $this->entity->getInfo( 'fb_page_url' ) . '/app/' . $fb_app_id;
						$fb_page_url = str_replace( "//app/", "/app/", $fb_page_url );

						$this->appId   = $fb_app_id;
						$this->pageId  = $fb_page_id;
						$this->pageUrl = $this->entity->getInfo( 'fb_page_url' );
						$this->pageTab = $fb_page_url;
						// Only use this information, when explicitly requested
						if ( isset( $_GET['ref_app_env'] ) && $_GET['ref_app_env'] == "fb" ) {
							$this->facebook['use_as_target'] = true;
						} else {
							$this->facebook['use_as_target'] = false;
						}
					}
				}
			}
		}

		// Initializes Facebook canvas information
		if ( $fb_app_id && $this->entity->getInfo( 'fb_app_namespace' ) ) {
			$this->facebook['app_namespace'] = $this->entity->getInfo( 'fb_app_namespace' );
			$this->appId        = $fb_app_id;
			$canvas_url                      = 'https://apps.facebook.com/' . $this->facebook['app_namespace'] . '/?entityId=' . $this->entityId;
			$this->facebook['canvas_url']    = $canvas_url;
		}

	}
}