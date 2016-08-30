<?php
/**
 * Template for your SmartLink file, which manages all the redirects and cache management on your server
 */
define("ROOT_PATH", realpath(dirname(__FILE__)));
require ROOT_PATH . '../../../../autoload.php';

if (!isset($_GET['projectId'])) {
    echo "No Model ID (projectId) available";
    exit();
}
$am = new \AppArena\AppManager($_GET['projectId'], array("cache_dir" => ROOT_PATH . "/var/cache"));

// Customize your sharing experience
$am->setMeta(array(
        "title" => "The lion king",
        "desc" => "The lion king is ready too be shared...",
        "image" => "https://c2.staticflickr.com/4/3734/9566728014_039da2e73e_h.jpg"
    ));
// And add some custom parameters to the url
$am->addParams(array(
        "age" => 16,
        "name" => "King of Bongobong!"
    ));

if (isset($_GET['action'])) {
    switch ($_GET['action']){
        case "clean":
            $am->cleanCache();
            echo "Cache successfully cleaned.";
            exit();
            break;
        case "share":
            $am->renderSharePage();
            break;
    }
}

echo "No action submitted";
exit();