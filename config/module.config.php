<?php
return array(
	'service_manager' => array(
		'factories' => array(
			'ZF2Assetic\Settings'		=>	'ZF2Assetic\Model\Factory\SettingsFactory',
		),
		'invokables' => array(
			'ZF2Assetic\CacheWorker'	=>	'ZF2Assetic\Model\Worker\CacheWorker',
			'ZF2Assetic\AssetHandler'	=>	'ZF2Assetic\Model\Service\AssetHandler',
		),
	),
	'view_helpers' => array(
		'invokables' => array(
			'assetHelper'			=> 'ZF2Assetic\View\Helper\AssetHelper',
		),
	),
	'zf2assetic' => array(
		'cache'						=> false,
		'cacheBusting'				=> false,
		'debug'						=> false,

		'filters' => array(
			'CssMinFilter'			=> 'Assetic\Filter\CssMinFilter',
			'JSMinFilter'			=> 'Assetic\Filter\JSMinFilter',
		),

		'paths' => array(
			'application_root'		=> getcwd(),
			'webserver'				=> '/public/assets',
			'web'					=> './assets',
		),
	),
);