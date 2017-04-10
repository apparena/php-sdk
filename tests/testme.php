<?php
ini_set( 'display_errors', 1 );

// Instantiate new App-Manager connection

// In your index.php
require __DIR__ . '/../vendor/autoload.php';

// Add App-Arena App-Manager
$am = new \AppArena\AppManager(
	[
		'versionId' => 340, // Add the version ID of your project here
		//'root_path' => __DIR__,
		'cache'     => [
			//'adapter' => new Symfony\Component\Cache\Adapter\PdoAdapter('yourdsn'),
			'directory' => __DIR__ . '/var/cache', // Default file cache directory
			'redis'     => [
				'host' => 'localhost',
				'port' => 6379
			]
		], // Writable folder for file cache. Check the cache section for more options
		'apikey'    => 'qtbWCZVEzsPeFxqE7fLdJGybEgiEdawxNZEdkFny' // Add you API key here
	]
);
// Get config values, translations and infos from the current app, template or version
$configs      = $am->getConfigs();
$languages    = $am->getLanguages();
$translations = $am->getTranslations();
$infos        = $am->getInfos();

var_dump($am);
