<?php
define( 'ROOT_PATH', realpath( __DIR__ ) . '/..' );
require ROOT_PATH . '/vendor/autoload.php';

$debug_mode = false;
if ( ! empty( $_GET['debug'] ) ) {
	$debug_mode = true;
}

// Setup App-Arena App-Manager connection
$config = [
	'versionId' => 123,
	'root_path' => __DIR__ . '/', // Root path accessible for the web
	'cache'     => [
		'dir' => __DIR__ . '/var/cache'
	],
	'apikey'    => '1234567890'
];
$am   = new \AppArena\AppManager( $config );

// Customize your meta data for the smartlink
$am->setMeta([
	"title" => $am->getConfig( "mod_share_title" ),
	"desc"  => $am->getConfig( "mod_share_desc" ),
	"image" => $am->getConfig( "mod_share_image" ),
] );

// Renders the HTML output for the SmartLink incl. a JS redirection
$am->renderSharePage( $_GET['debug'] ?? false );