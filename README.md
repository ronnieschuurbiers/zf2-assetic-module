# ZF2 Assetic Examples module #

The ZF2 Assetic module is available here: [ZF2 Assetic module](https://github.com/magnetronnie/zf2-assetic-module).


## Installing
------

1. Add this module to the `/vendor/` directory of your Zend 2 application.

2. Add this module to `modules` and `module_paths` in the `application.config.php` file.

``` php
   	'modules' => array(
   		'ZF2AsseticExamples',
   	),
   	'module_listener_options' => array(
   		'module_paths' => array(
			'ZF2AsseticExamples' => './vendor/zf2-assetic-module-examples',
   		),
   	),
```

3. Navigate to `http://your_web_application/zf2assetic` in your browser.