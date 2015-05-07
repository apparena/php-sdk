<?php
use Symfony\Component\HttpFoundation\Request;
use AppManager\RestProxy\RestProxy;
use AppManager\RestProxy\CurlWrapper;

/* config settings */
define("ROOT_PATH", __DIR__ . '/../../../../..' ); // Set include path
require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/config/basic.php';
require_once ROOT_PATH . '/config/local.php';
$config_data = array_merge($config_core, $config_data);
$es_config = $config_data["elasticsearch"];
$host = $es_config['host'];
$user = $es_config['user'];
$pass = $es_config['pass'];

$request_body = file_get_contents('php://input');
$json = json_decode($request_body);

// Example for additional Curl request headers and additional curl options for all requests
$requestHeaders = array(
    'Authorization: Basic '. base64_encode($es_config['user'] . ":" . $es_config['pass'])
);
$curlOptions = array(
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_SSL_VERIFYHOST => 0
);
$proxy = new RestProxy(
    Request::createFromGlobals(),
    new CurlWrapper($requestHeaders, $curlOptions)
);

//$proxy->register('github/example/With/2/destinations', 'https://api.github.com');
//$proxy->register('esproxy/github', 'https://api.github.com');
//$proxy->register('esproxy/instances', 'https://manager.app-arena.com/api/v1/instances');
$proxy->register('/', $es_config['host']);
$proxy->run();

foreach ($proxy->getHeaders() as $header) {
    header($header);
}
echo $proxy->getContent();
exit();