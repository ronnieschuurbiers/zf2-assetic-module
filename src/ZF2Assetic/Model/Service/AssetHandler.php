<?php
namespace ZF2Assetic\Model\Service;

use Assetic\AssetManager;
use Assetic\AssetWriter;
use Assetic\Factory\AssetFactory;
use Assetic\FilterManager;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\RendererInterface;

/**
 * The AssetHandler builds all the assets, writes them to the webserver and injects them into the application.
 *
 * @author Ronnie Schuurbiers, Pronexus
 * @license https://github.com/magnetronnie/zf2-assetic-module/blob/master/LICENSE
 */
class AssetHandler implements ServiceLocatorAwareInterface {

	protected $serviceLocator;
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator) { $this->serviceLocator = $serviceLocator; }
	public function getServiceLocator() { return $this->serviceLocator; }

	protected $assetFactories = array();
	protected $assetManagers = array();
	protected $filterManager;


	/**
	 * @param $id string
	 * @param $settings Settings
	 */
	public function createAssetFactoryWithManagers($id, $settings) {
		$this->assetFactories[$id]	= new AssetFactory($settings->getPaths()['application_root']);

		if($settings->getCacheBusting() !== null && $settings->getCacheBusting() == 'filename') {
			$worker = $this->getServiceLocator()->get('ZF2Assetic\CacheBustingWorker');
			$this->assetFactories[$id]->addWorker($worker);
		}

		$this->assetManagers[$id]	= new AssetManager();

		$this->assetFactories[$id]->setAssetManager($this->assetManagers[$id]);
		$this->assetFactories[$id]->setFilterManager($this->filterManager);
		$this->assetFactories[$id]->setDebug($settings->getDebug());
	}

	/**
	 * @param $settings Settings
	 * @throws \Exception
	 */
	public function cleanup($settings) {
		if($settings->getCleanUp()) {
			if($settings->getCacheBusting() === null || $settings->getCacheBusting() !== null && $settings->getCacheBusting() != 'filename') {
				$assetTargets = array();
				$dir = $settings->getPaths()['application_root'] . $settings->getPaths()['webserver'];

				if(file_exists($dir)) {
					foreach($settings->getAssets() as $asset) {
						$assetTargets[] = $dir . '/' . $asset['target'];
					}

					// Delete files
					if($dirPath = realpath($dir)) {
						$di = new \RecursiveDirectoryIterator($dirPath);
						$di->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
						foreach (new \RecursiveIteratorIterator($di) as $filelocation => $file) {
							if(!in_array($filelocation, $assetTargets)) {
								if($path = realpath($filelocation)){
									if(file_exists($path)){
										if(is_writable($path)){
											if(!unlink($path)){
												throw new \Exception('Couldn\'t delete file ' . $path . '.');
											}
										} else {
											throw new \Exception('Not enough access to delete file ' . $path . '.');
										}
									}
								} else {
									throw new \Exception('Couldn\'t access file ' . $filelocation . '.');
								}
							}
						}

						// Delete empty directories
						$di = new \RecursiveDirectoryIterator($dirPath, \RecursiveDirectoryIterator::SKIP_DOTS);
						$directories = new \ParentIterator($di);
						foreach (new \RecursiveIteratorIterator($directories, \RecursiveIteratorIterator::CHILD_FIRST) as $dir) {
							if (iterator_count($di->getChildren()) === 0) {
								$realpath = realpath($dir->getPathname());
								rmdir($realpath);
							}
						}
					} else {
						throw new \Exception('Couldn\'t access directory ' . $dir . '.');
					}
				}
			}
		}
	}

	/**
	 * @param $settings Settings
	 */
	public function buildAssets($settings) {
		$this->filterManager = new FilterManager();
		$this->createAssetFactoryWithManagers('build', $settings);
		$this->createAssetFactoryWithManagers('nobuild', $settings);

		foreach($settings->getAssets() as $assetName => $asset) {
			if($this->leafsExist($settings, $assetName, $asset)) {
				$filters = $this->addFilters($settings, $asset);

				// Assets that will be written to file
				if(isset($asset['target'])) {
					$options = array(
						'output' => $asset['target']
					);
					$asseticAsset = $this->assetFactories['build']->createAsset($asset['leafs'], $filters, $options);
					$asseticAsset = $this->cacheAsset($settings, $asseticAsset);
					$this->assetManagers['build']->set($assetName, $asseticAsset);

				// Assets which are only used inline
				} else {
					$asseticAsset = $this->assetFactories['nobuild']->createAsset($asset['leafs'], $filters);
					$asseticAsset = $this->cacheAsset($settings, $asseticAsset);
					$this->assetManagers['nobuild']->set($assetName, $asseticAsset);
				}
			}
		}
	}

	/**
	 * @param $settings Settings
	 * @param $assetName
	 * @param $asset
	 * @return bool
	 */
	protected function leafsExist($settings, $assetName, $asset) {
		foreach($asset['leafs'] as $leaf) {
			// We can't check wildcards
			if(strpos($leaf, '*') === false) {
				if(!file_exists($leaf)) {
					trigger_error("Leaf '" . $leaf . "' in asset '" . $assetName . "' could not be found", E_USER_NOTICE);
					$assets = $settings->getAssets();
					unset($assets[$assetName]);
					$settings->setAssets($assets);
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * AssetCache/FilesystemCache generates extension-less files in the cache dir. It's not versatile enough so we use custom code in the Writer functions.
	 *
	 * @param $settings Settings
	 * @param $asset
	 * @return mixed
	 */
	protected function cacheAsset($settings, $asset) {
		return $asset;
//		return $settings->getCache()
//			? new AssetCache($asset, new FilesystemCache($settings->getPaths()['cache']))
//			: $asset;
	}

	/**
	 * @param $settings Settings
	 * @param $assets
	 * @return array
	 * @throws \Exception
	 */
	public function addFilters($settings, $assets) {
		$filters = array();

		if(isset($assets['filters'])) {
			foreach($assets['filters'] as $filterAlias) {
				$filterAliasNoDebug = ltrim($filterAlias, '?');
				if(isset($settings->getFilters()[$filterAliasNoDebug])) {
					$filters[] = $filterAlias;

					if(!$this->filterManager->has($filterAliasNoDebug)) {
						$filterClassName = $settings->getFilters()[$filterAliasNoDebug];
						$filter = new $filterClassName();
						$this->filterManager->set($filterAliasNoDebug, $filter);
					}
				} else {
					throw new \Exception('Trying to apply filter ' . $filterAliasNoDebug . ', but filter class not found in module config.');
				}
			}
		}

		return $filters;
	}

	/**
	 * @param $settings Settings
	 */
	public function writeAssets($settings) {
		$dir = $settings->getPaths()['application_root'] . $settings->getPaths()['webserver'];
		$writer = new AssetWriter($dir);

		foreach($this->assetManagers['build']->getNames() as $assetName) {
			$asset = $this->assetManagers['build']->get($assetName);
			$assetLocation = realpath($dir . '/' . $asset->getTargetPath());

			$assetExists = is_file($assetLocation);
			if($settings->getCache() !== false) {
				$assetChanged = false;
				if($assetExists) {
					if($settings->getCache() == 'lastmodified') {
						$assetChanged = $assetExists && filemtime($assetLocation) < $asset->getLastModified();
					} else if($settings->getCache() == 'checksum') {
						$assetHash = hash_file("md5", ($assetLocation), false);
						$fileHash = hash("md5", $asset->dump(), false);
						$assetChanged = $assetExists && $assetHash != $fileHash;
					}
				}

				if (!$assetExists || $assetChanged) {
					$writer->writeAsset($asset);
				}
			} else if($assetExists && $settings->getAllowOverwrite() || $settings->getAllowOverwrite()) {
				$writer->writeAsset($asset);
			}
		}
	}

	/**
	 * @param $settings Settings
	 * @param $renderer RendererInterface
	 */
	public function injectAssets($settings, $renderer) {
		foreach($settings->getAssets() as $assetName => $asset) {
			if(isset($asset['viewHelper'])) {
				switch($asset['viewHelper']) {
					case 'HeadLink':
						$headLink = $renderer->plugin('HeadLink');
						// Due to a bug in ZF2, it's not possible to have both $extras AND $conditionalStylesheet
						//  (when using appendStylesheet it will force rel='stylesheet' even when it's overriden in the $extras parameter)
						if(isset($asset['viewHelperOptions']['conditional'])) {
							$headLink->appendStylesheet(
								$this->formatAssetLocation($settings, $assetName),
								'screen',
								$asset['viewHelperOptions']['conditional'],
								array()
							);
						} else {
							$headLinkParams = array(
								'href' => $this->formatAssetLocation($settings, $assetName),
								// Let's assume it's css.
								'rel' => 'stylesheet',
								'type' => 'text/css'
							);
							if(isset($asset['viewHelperOptions'])) {
								$headLinkParams = array_merge($headLinkParams, $asset['viewHelperOptions']);
							}
							$headLink($headLinkParams, 'APPEND');
						}
					case 'HeadStyle':
						$renderer->plugin('HeadStyle')->appendStyle($this->assetManagers['nobuild']->get($assetName)->dump());
						break;
					case 'HeadScript':
						$type	= (isset($asset['viewHelperType']) ? $asset['viewHelperType'] : 'text/javascript');
						$attrs	= (isset($asset['viewHelperOptions']) ? $asset['viewHelperOptions'] : array());
						if(isset($asset['target'])) {
							$src	= $this->formatAssetLocation($settings, $assetName);
							$renderer->plugin('HeadScript')->appendFile($src, $type, $attrs);
						} else {
							$script	= $this->assetManagers['nobuild']->get($assetName)->dump();
							$renderer->plugin('HeadScript')->appendScript($script, $type, $attrs);
						}
						break;
				}
			}
		}
	}

	/**
	 * @param $assetName
	 * @param $renderer RendererInterface
	 */
	public function injectAsset($assetName, $renderer) {
		/** @var Settings $settings */
		$settings = $this->getServiceLocator()->get('ZF2Assetic\Settings');
		if(isset($settings->getAssets()[$assetName]['target'])) {
			return $renderer->plugin('InlineScript')->setFile($settings->getPaths()['web'] . '/' . $settings->getAssets()[$assetName]['target']);
		} else {
			return $renderer->plugin('InlineScript')->setScript($this->assetManagers['nobuild']->get($assetName)->dump());
		}
	}

	/**
	 * @param $settings Settings
	 * @param $assetName
	 * @return string Asset file location
	 */
	protected function formatAssetLocation($settings, $assetName) {
		$assetLocation = $settings->getPaths()['web'] . '/' . $this->assetManagers['build']->get($assetName)->getTargetPath();
		if($settings->getCacheBusting() !== null && $settings->getCacheBusting() == 'querystring') { $assetLocation .= '?lm=' . $this->assetManagers['build']->get($assetName)->getLastModified(); }
		return $assetLocation;
	}
}