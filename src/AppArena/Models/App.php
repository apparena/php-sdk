<?php
/**
 * App-Manager (http://app-arena.readthedocs.org/en/latest/)
 *
 * @link      http://app-arena.readthedocs.org/en/latest/ for complete API and developer documentation
 * @copyright App-Arena.com - iConsultants GmbH (http://www.app-arena.com)
 * @license   2015 -
 */

namespace AppArena\Models;

/**
 * Class Instance Instance object
 */
class App extends AbstractEntity
{
	protected $projectId = false; // ID of this instances app model / Project
	/** @var  integer $templateId */
	protected $templateId; // ID of this instances template
	protected $expiryDate = null;

	/**
	 * Initialize the App object from API
	 * @param int $id App ID if already available
	 * @param Api $api Api object
	 * @throws \InvalidArgumentException Throws an error, when no App ID available
	 */
	public function __construct($id = null, Api $api)
	{
		$this->type = 'app';

		// If no App ID available, then try to recover it
		if (!$id) {
			$id = $this->recoverId();
		}

		parent::__construct($id, $api);
	}

	/**
	 * @return integer
	 */
	public function getTemplateId()
	{
		return $this->templateId;
	}

	/**
	 * @param integer $templateId
	 */
	public function setTemplateId($templateId)
	{
		$this->templateId = $templateId;
	}


	/**
	 * @return mixed
	 */
	public function getExpiryDate()
	{
		return $this->expiryDate;
	}

	/**
	 * @param mixed $expiryDate
	 */
	public function setExpiryDate($expiryDate)
	{
		$this->expiryDate = $expiryDate;
	}





	/**
	 * Returns if the current request contains admin authentication information (GET-params)
	 * @param String $projectSecret The project secret to validate the Hash
	 * @return bool Returns if the current request contains admin authentication information
	 */
	public function isAdmin( $projectSecret ) {
		// Try to get Hash and Timestamp from the request parameters
		if (isset($_GET['hash'], $_GET['timestamp'])) {
			$hash      = $_GET['hash'];
			$timestamp = $_GET['timestamp'];
			if ($hash === sha1($this->getId() . '_' . $projectSecret . '_' . $timestamp) && $timestamp >= strtotime('-1 hours')) {
				return true;
			}
		}

		return false;
	}


	public function save(Api $api = null) {
		if ($api == null) {
			$api = $this->getApi();
		}
		$body = [
			"name" => $this->name,
			"templateId" => $this->templateId,
			"lang" => $this->lang
		];
		if ($this->expiryDate != null) {
			$body["expiryDate"] = $this->expiryDate;
		}
		if ($this->companyId != null) {
			$body["companyId"] = $this->companyId;
		}
		$result = json_decode($api->post("apps", $body), true);
		if($result["data"]) {
			if ($appid = $result["data"]["appId"]) {
				$this->setId($appid);
			}
			if ($companyId = $result["data"]["companyId"]) {
				$this->setCompanyId($companyId);
			}
			if ($expiryDate = $result["data"]["expiryDate"]) {
				$this->setExpiryDate($expiryDate);
			}
		}
	}

	/**
	 * setConfig
	 *
	 * Sets the config parameters of an existing config value and cleans the cache.
	 * Returns false on error
	 *
	 * @param string $key configId to be changed
	 * @param string $value new value to be used
	 * @param string|null $name new name to be used. Set to null if you don't want to change it
	 * @param string|null $type new type. Set to null if you don't want to change it
	 * @param string|null $description new description Set to null if you don't want to change it
	 * @param Api|null $api supply a custom Api object. Set to null to use the instance's Api object
	 * @return array|bool
	 */
	public function setConfig($key, $value, $name = null, $type = null, $description = null, Api $api = null) {
		if ($api == null) {
			$api = $this->getApi();
		}

		// Update the language for the current request
		$this->api->setLang($this->getLang());
		$response = $this->api->get("apps/$this->id/configs");

		if ($response == false) {
			return false;
		}

		$config = $response['_embedded']['data'];

		if (isset($config[$key])) {
			$config[$key]['configId'] = $key;
			$config[$key]['type'] = ($type != null)?$type : $config[$key]['type'];
			$config[$key]['name'] = ($name != null)?$name : $config[$key]['name'];
			$config[$key]['value'] = ($value != null)?$value : $config[$key]['value'];
			$config[$key]['description'] = ($description != null)?$description : $config[$key]['description'];
		}else {
			return false;
		}

		$this->config = $config;
		$route = "apps/" . $this->getId();
		$response = json_decode($this->api->put($route . "/configs/" . $key, $config[$key]), true);

		if ($response['status'] != 200) {
			return false;
		}

		$cache_key = str_replace('/', '_', $route) . "_" . md5($route . '[]');
		$api->cleanCache($cache_key);

		$cache_key = str_replace('/', '_', $route . '/configs/' . $this->lang) . "_" . md5($route . '/configs[]');
		$api->cleanCache($cache_key);
		return $this->config;
	}

	/**
	 * Tries to get the instance ID from the current environment (e.g. Cookies, Facebook, Request-Parameters)
	 * @params array $params Additional information helping to descover the instance ID
	 */
	public function recoverId()
	{
		$id = false;

		// Try to get the ID from the REQUEST
		if (isset($_REQUEST['appId'])) {
			$id = $_REQUEST['appId'];
		} else {
			if (isset($_SERVER['appId'])) {
				$id = $_SERVER['appId'];
			} else {
				// Try to get the ID from the facebook fanpage tab and projectId (app model)
				$id = $this->getIdFromFBRequest();

				if (!$id) {
					// Try to get the ID from a cookie
					if (isset($_COOKIE['aa_appId'])) {
						$id = $_COOKIE['aa_appId'];
					} else {
						// Try to get the ID from the user session
						if (!empty($_SESSION['current_appId'])) {
							$id = $_SESSION["current_appId"];
						}
					}
				}
			}
		}

		// Set ID to the object and the users session and cookie
		if ($id) {
			$_SESSION['current_appId'] = intval($id);
			$this->id                 = intval($id);
		}

		return $this->id;
	}


	/**
	 * Tries to get the Language settings from the current environment (e.g. Cookies, Request-Parameters, Facebook)
	 */
	private function recoverLangTag()
	{

		$lang = false;
		if (isset($_GET['lang'])) {
			$lang = $_GET['lang'];
		} else {
			if (isset($_GET['locale'])) {
				$lang = $_GET['locale'];
			} else {
				if (isset($app_data) && isset($app_data['locale'])) {
					$lang = $app_data['locale'];
				} else {
					if (isset($_COOKIE['aa_' . $this->id . '_lang'])) {
						$lang = $_COOKIE['aa_' . $this->id . '_lang'];
					}
				}
			}
		}

		if ($lang) {
			$this->setLang($lang);
		}

		return $this->lang;
	}

	/**
	 * Returns and sets the instance_id by requesting the API for data
	 */
	private function getIdFromFBRequest()
	{
		$app_data   = array();
		$fb_page_id = false;
		$appId       = false;

		if (isset($_REQUEST['signed_request'])) {
			list($encoded_sig, $payload) = explode('.', $_REQUEST['signed_request'], 2);
			$signed_request = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
			if (isset($signed_request['app_data'])) {
				$app_data = json_decode($signed_request['app_data'], true);
			}

			if (isset($signed_request['page']['id']) && $signed_request['page']['id']) {
				$fb_page_id = $signed_request['page']['id'];
			}

			if ($fb_page_id && $this->projectId) {
				$request_url = "https://manager.app-arena.com/api/v1/env/fb/pages/" . $fb_page_id .
				               "/instances.json?projectId=" . $this->projectId . "&active=true";
				$instances   = json_decode(file_get_contents($request_url), true);
				foreach ($instances['data'] as $instance) {
					if ($instance['activate'] == 1) {
						$appId = $instance['appId'];
					}
				}
			}
		}

		return $appId;
	}

	/**
	 * @return boolean
	 */
	public function getProjectId()
	{
		if ($this->projectId) {
			return $this->projectId;
		}

		$this->projectId = $this->getInfo("projectId");

		return $this->projectId;
	}

}
