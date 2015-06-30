# ZF2 Assetic module #

The ZF2 Assetic module is a [Zend Framework 2](https://github.com/zendframework/zf2) module to use [Assetic](https://github.com/kriswallsmith/assetic/) (an asset management framework for PHP) in your Zend 2 application.

An example module is available here: [ZF2 Assetic Examples module](https://github.com/magnetronnie/zf2-assetic-examples-module).
This project was loosely inspired by [AsseticBundle](https://github.com/widmogrod/zf2-assetic-module), a similar project.


## Features:

 * Manage assets (of course!);
 * Keep source files with the module rather than everything in the `public` directory;
 * Use Assetic filters;
 * Merge assets;
 * Link assets in the `<head>` and `<body>` of a page using Zend 2 plugins;
 * Display assets as plain text instead of links;
 * Cache assets to reduce server load;
 * Force browsers to refresh their cache after an asset was updated.


## Installing

1) Make sure you have [Assetic](https://github.com/kriswallsmith/assetic/) added to your Zend 2 application.

2) Add the ZF2 Assetic module to the `/vendor/` directory of your Zend 2 application.

3) Add the ZF2 Assetic module to `modules` and `module_paths` in the `application.config.php` file.

``` php
   	'modules' => array(
   		'ZF2Assetic',
   	),
   	'module_listener_options' => array(
   		'module_paths' => array(
   			'ZF2Assetic' => './vendor/zf2-assetic-module',
   		),
   	),
```

4) Change the module's settings in `module.config.php`. See [Module Configuration](#module-configuration) below for options.

5) Add assets to the `module.config.php` of any module that uses assets. See [Assets Configuration](#assets-configuration) below for options.

6) Make sure you use Zend's plugins to display the assets. See [Displaying assets](#displaying-assets) below for options.

7) Enjoy!




## Module Configuration

The ZF2 Assetic module configuration is located in: `zf2-assetic-module/config/module.config.php`.

``` php
'zf2assetic' => array(
	'allowOverwrite'			=> true,
	'cache'						=> false,
	'cacheBusting'				=> 'querystring',
	'debug'						=> false,
	'cleanup'					=> true,

	'filters' => array(
		'CssMinFilter'			=> 'Assetic\Filter\CssMinFilter',
	),

	'paths' => array(
		'application_root'		=> getcwd(),
		'webserver'				=> '/public/assets',
		'web'					=> './assets',
	),
),
```

### Allow overwriting

If caching is disabled, the ZF2 Assetic module will write the assets to the webserver on every request. To only write assets that do not exist yet, disable the `allowOverwrite` config option. This means you will have to manually empty the assets directory on the webserver if any assets are updated.


### Caching

The ZF2 Assetic module has some basic built-in support for (server side) caching. This is disabled by default, but can be enabled in the configuration.

| **Config key** | **Config value** | **Description** |
|----------------|------------------|-----------------|
| `cache`        | `false`          | No caching; files are written to the website on every request. |
| `cache`        | `lastmodified`   | If the asset in Assetic is newer then the file on the webserver, the asset will be written to the webserver path. |
| `cache`        | `checksum`       | If the checksum of the dumped asset in Assetic is different from the checksum of the file on the website, the asset will be written to the webserver path. |


### Cache busting

The aim of cache busting is to prevent a browser from caching assets (client side). The ZF2 Assetic module has two cache busting options:

| **Config key** | **Config value** | **Description** |
|----------------|------------------|-----------------|
| `cacheBusting` | `filename`       | The last modified time of the asset file is added to the file name. For example 'scripts.js' will be renamed to 'scripts_1421319156.js'. |
| `cacheBusting` | `querystring`    | The last modified time of the asset file is added as query string. For example 'scripts.js' will be included into the html as 'scripts.js?lm=1421319156' |


### Clean up

The `cleanup` config option will make sure the ZF2 Assetic module will remove any older asset or other files in the directory specified with the `webserver` path config option. Empty folders are also automatically removed.

Please note: Clean up will not work when using the cache busting `filename` option (because the files are getting a unique name and ZF2 Assetic module is not keeping a list of these names, it can't possibly know what files not to remove).


### Debugging

Debugging can be enabled in the configuration. The value will be passed to Assetic and is not used by the ZF2 Assetic module. When enabled Assetic will skip filters whose alias start with a '?' (see filters below).


### Filters

ZF2 Assetic module supports all Assetic filters. They are placed as 'name/class name' pairs in the configuration.

**Installing filters (without Composer):**

1. Add the external library to `zf2-assetic-module/libs/`.

2. Add the filter to `zf2-assetic-module/autoload_classmap.php`. For example:

``` php
return array(
	'CssMin'		=> __DIR__ . '/libs/cssmin-v2.0.2.2.php',
);
```


### Paths

``` php
'paths' => array(
	'application_root'		=> getcwd(),
	'webserver'				=> '/public/assets',
	'web'					=> './assets',
),
```

| **Key** | **Description** |
|-----------------|-----------------------------------|
| application_root  | Absolute path to the application's root. |
| webserver         | Path to the location on the webserver where the generated assets will be placed, relative to the application root. |
| web               | Path to the location on the website where the generated assets will be accessed by the website, relative to the website's root url. |




## Assets Configuration

Assets are configured in any module's `module.config.php`.


### Assets and leafs

``` php
'zf2assetic' => array(
	'assets' => array(
		'css' => array(
			'target' => 'css.css',
			'viewHelper' => 'HeadLink',
			'leafs' => array(
				__DIR__ . '/../view/assets/css/css.css',
			),
			'filters' => array(
				'?CssMinFilter',
			),
		),
		'javascript' => array(
			...
		),
	),
),
```

| **Key** | **Description** |
|-----------------|-----------------------------------|
| assets     | An array of assets. Use unique names unless you want to merge assets from different module together. |
| target     | The file name of the target asset that will be created on the webserver. Remove this to display assets as text instead of links in the html. |
| viewHelper | Zend plugin to use for this asset (see below) |
| leafs      | Leafs are the source files in the module's asset directory. |
| filters    | An array of filter names (as defined in the module configuration) that should be applied to this asset. Add a question mark to prevent this filter during debugging. |


## Displaying assets

ZF2 Assetic module uses Zend's plugins to display the assets.

To display them in the `<head>`:

``` html
<head>
	<title>ZF2 Assetic Module - Examples module</title>
<?php
echo $this->headLink();
echo $this->headScript();
echo $this->headStyle();
?>
</head>
```

To display them somewhere in the `<body>`:

``` html
<div>
<?php echo $this->assetHelper()->injectAsset('asset_name_here'); ?>
</div>
```

This following plugins are supported:

| **Plugin**      | **HTML markup** | **Displayed as** |
|-----------------|-----------------|------------------|
| HeadLink        | `<link>`        | link             |
| HeadStyle       | `<style>`       | text             |
| HeadScript*     | `<script>`      | link or text     |
| InlineScript*   | `<script>`      | link or text     |

\* The asset will be displayed as link instead of text if a `target` is set in the assets configuration.

It's possible to add custom options to a HeadLink with `viewHelperOptions`. For example this is how a favicon is added:

``` php
'zf2assetic' => array(
	'assets' => array(
		'favicon' => array(
			'target' => 'favicon.ico',
			'viewHelper' => 'HeadLink',
			'viewHelperOptions' => array(
				'rel' => 'shortcut icon',
				'type' => 'image/x-icon'
			),
			'leafs' => array(
				__DIR__ . '/../view/assets/favicon/favicon.ico',
			),
		),
	),
),
```


### Future module extensions/improvements

* Support for images.
* Generic path resolver.
* Making clean up work with file name cache busting.
