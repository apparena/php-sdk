# php-sdk
App-Manager PHP SDK

Read the documentation on http://app-arena.readthedocs.org/


## Usage

Use the Composer Autoloader to start using the App-Manager

require ROOT_PATH . '/vendor/autoload.php';

// Clear the cache before, requestion Instance in for each
$app_manager = new \AppManager\AppManager(
    array(
        'cache_dir' => ROOT_PATH . "/var/cache"
    )
);
$instance    = $app_manager->getInstance(0, array('m_id' => $m_id));
$config      = $instance->getConfigs();
$translation = $instance->getTranslations();
$info        = $instance->getInfo();


## Smart-Link

Smart-Link technology provides intelligent sharing link generation for all type of devices. 