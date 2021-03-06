<?php

namespace AppArena\Models\Environment;

use AppArena\Models\Entities\AbstractEntity;

/**
 * All functionality related to the Facebook Page Tab environment
 * Class Facebook
 * @package AppArena\Models
 */
class Facebook extends AbstractEnvironment
{

	private $appId;
	private $pageId;
	private $pageUrl;
	private $pageTab;
	private $signedRequest;

	/**
	 * Facebook constructor.
	 *
	 * @param AbstractEntity $entity
	 */
	public function __construct(AbstractEntity $entity)
	{

		parent::__construct($entity);
		$this->type     = 'facebook';
		$this->priority = 10;

		// Get Facebook page tab parameters and write them to GET parameters
		if (isset($_REQUEST['signed_request'])) {
			$this->signedRequest = $_REQUEST['signed_request'];
			list($encoded_sig, $payload) = explode('.', $this->signedRequest, 2);
			$fb_signed_request = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

			// So use the current Facebook page for sharing, if no fb_page_id is defined via GET
			if (isset($fb_signed_request['page']['id']) && !isset($_GET['fb_page_id'])) {
				$_GET['fb_page_id'] = $fb_signed_request['page']['id'];
			}
			// And the parameter-passthrough mechanism, will reset all GET parameters from app_data
			if (isset($fb_signed_request['app_data'])) {
				$params = json_decode(urldecode($fb_signed_request['app_data']), true);
				foreach ($params as $key => $value) {
					if (!isset($_GET[$key])) {
						$_GET[$key] = $value;
					}
				}
			}
		} else {
			$this->signedRequest = false;
		}

		// Initialize Facebook Information ... (and check if the SmartLink should redirect to Facebook)
		$fb_app_id  = $this->entity->getInfo('fb_app_id');
		if (isset($_GET['fb_app_id']) && $_GET['fb_app_id']) {
			$fb_app_id = $_GET['fb_app_id'];
		}
		if ($fb_app_id) {
			if (isset($_GET['fb_page_id'])) {
				// ... from GET-Parameter
				$this->appId   = $fb_app_id;
				$this->pageId  = $_GET['fb_page_id'];
				$this->pageUrl = 'https://www.facebook.com/' . $this->pageId;
				$this->pageTab = $this->pageUrl . '/app/' . $this->appId;
			} else {
				$facebook = $this->getCookieValue("facebook");
				if (isset($facebook['page_id'])) {
					// ... from COOKIE-Parameter
					$this->appId   = $fb_app_id;
					$this->pageId  = $facebook['page_id'];
					$this->pageUrl = 'https://www.facebook.com/' . $this->pageId;
					$this->pageTab = $this->pageUrl . '/app/' . $this->appId;
				} else {
					// ... from the App Channels
					$channels = $this->entity->getChannels();
					if (is_array($channels)) {
						foreach ($channels as $channel) {
							if ($channel['type'] === 'facebook') {
								$this->appId   = $fb_app_id;
								$this->pageId  = $channel['pageId'];
								$this->pageUrl = 'https://www.facebook.com/' . $this->pageId;
								$this->pageTab = $this->pageUrl . '/app/' . $this->appId;
								break;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @return array|bool|String
	 */
	public function getAppId()
	{
		return $this->appId;
	}

	/**
	 * @return string
	 */
	public function getPageUrl()
	{
		return $this->pageUrl;
	}

	/**
	 * Returns all relevant Environment information as array
	 */
	public function toArray()
	{
		return [
			'priority' => $this->getPriority(),
			'type' => $this->getType(),
			'pageId' => $this->getPageId()
		];
	}

	/**
	 * @return mixed
	 */
	public function getPageId()
	{
		return $this->pageId;
	}

	/**
	 * @return string
	 */
	public function getPageTab()
	{
		return $this->pageTab;
	}

	/**
	 * @return string
	 */
	public function getSignedRequest()
	{
		return $this->signedRequest;
	}


}