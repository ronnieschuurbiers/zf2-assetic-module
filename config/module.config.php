<?php
return array(
	'service_manager' => array(
		'factories' => array(
			'ZF2Assetic\Settings'			=>	'ZF2Assetic\Model\Factory\SettingsFactory',
		),
		'invokables' => array(
			'ZF2Assetic\CacheBustingWorker'	=>	'ZF2Assetic\Model\Worker\CacheBustingWorker',
			'ZF2Assetic\AssetHandler'		=>	'ZF2Assetic\Model\Service\AssetHandler',
		),
	),
	'view_helpers' => array(
		'invokables' => array(
			'assetHelper'					=> 'ZF2Assetic\View\Helper\AssetHelper',
		),
	),
	'zf2assetic' => array(
		'allowOverwrite'			=> false,
		'cache'						=> false,
		'cacheBusting'				=> 'querystring',
		'debug'						=> false,
		'cleanup'					=> false,

		'filters' => array(
			'CssMinFilter'			=> 'Assetic\Filter\CssMinFilter',
			'JSMinFilter'			=> 'Assetic\Filter\JSMinFilter',
			'LessphpFilter'			=> 'Assetic\Filter\LessphpFilter',
		),

		'paths' => array(
			'application_root'		=> getcwd(),
			'webserver'				=> '/public/assets',
			'web'					=> './assets',
		),
	),
);