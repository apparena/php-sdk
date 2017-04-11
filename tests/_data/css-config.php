<?php
[
	'file1' => [
		'files'         => [
			__DIR__ . '/css/less/bootstrap-custom.less',
			__DIR__ . '/css/scss/bootstrap-social.scss',
			__DIR__ . '/js/vendor_bower/font-awesome/css/font-awesome.min.css'
		],
		'config_values' => [],
		'variables'     => [
			'brand-primary'       => $am->getConfig( 'color_primary' ), // Use a config value to set a color
			'border-radius-base'  => '0px',
			'border-radius-large' => '0px',
			'border-radius-small' => '0px',
		],
		'replacements'  => [
			'../fonts/fontawesome' => '../../js/vendor_bower/font-awesome/fonts/fontawesome'
		],
	],
	'file2' => [
		'files'         => [
			__DIR__ . '/css/style.css',
			__DIR__ . '/css/less/app.less',
		],
		'config_values' => [ 'css_app', 'css_user' ], // A list of config value IDs to include in the CSS
		'variables'     => [
			'primary'   => '#478AB8',
			'secondary' => '#2D343D',
			'highlight' => '#efefef',
		],
		'replacements'  => []
    ),
);