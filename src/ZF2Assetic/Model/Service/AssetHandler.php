<?php
namespace ZF2Assetic\Model\Service;

use Assetic\AssetManager;
use Assetic\AssetWriter;
use Assetic\Factory\AssetFactory;
use Assetic\FilterManager;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\RendererInterface;
use ZF2Assetic\Model\Factory\SettingsFactory;

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
	 * @param $settings SettingsFactory
	 */
	public function createAssetFactoryWithManagers($id, $settings) {
		$this->assetFactories[$id]	= new AssetFactory($settings->getPaths()['application_root']);

		if ($settings->getCacheBusting()) {
			$worker = $this->getServiceLocator()->get('ZF2Assetic\CacheWorker');
			$this->assetFactories[$id]->addWorker($worker);
		}

		$this->assetManagers[$id]	= new AssetManager();

		$this->assetFactories[$id]->setAssetManager($this->assetManagers[$id]);
		$this->assetFactories[$id]->setFilterManager($this->filterManager);
		$this->assetFactories[$id]->setDebug($settings->getDebug());
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
	 * @param $settings
	 * @param $assetName
	 * @param $asset
	 * @return bool
	 */
	protected function leafsExist($settings, $assetName, $asset) {
		foreach($asset['leafs'] as $leaf) {
			if(!file_exists($leaf)) {
				trigger_error("Leaf '" . $leaf . "' in asset '" . $assetName . "' could not be found", E_USER_NOTICE);
				$assets = $settings->getAssets();
				unset($assets[$assetName]);
				$settings->setAssets($assets);
				return false;
			}
		}
		return true;
	}

	/**
	 * AssetCache/FilesystemCache generates extension-less files in the cache dir. It's not versatile enough so we use custom code in the Writer functions.
	 *
	 * @param $settings
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

			if($settings->getCache()) {
				$assetExsists = is_file($dir . '/' . $asset->getTargetPath());
				$assetChanged = $assetExsists && filemtime($dir . '/' . $asset->getTargetPath()) < $asset->getLastModified();

				if (!$assetExsists || $assetChanged) {
					$writer->writeAsset($asset);
				}
			} else {
				$writer->writeAsset($asset);
			}
		}
	}

	/**
	 * @param $settings Settings
	 * @param $renderer
	 */
	public function injectAssets($settings, $renderer) {
		foreach($settings->getAssets() as $assetName => $asset) {
			switch($asset['viewHelper']) {
				case 'HeadLink':
					$headLinkParams = array(
						'href' => $settings->getPaths()['web'] . '/' . $this->assetManagers['build']->get($assetName)->getTargetPath(),
						// Let's assume it's css.
						'rel' => 'stylesheet',
						'type' => 'text/css'
					);
					if(isset($asset['viewHelperOptions'])) {
						$headLinkParams = array_merge($headLinkParams, $asset['viewHelperOptions']);
					}
					// We're not using appendStylesheet, because it will force rel='stylesheet' even when it's overriden in the $extras parameter.
					$headLink = $renderer->plugin('HeadLink');
					$headLink($headLinkParams, 'APPEND');
					break;
				case 'HeadStyle':
					$renderer->plugin('HeadStyle')->appendStyle($this->assetManagers['nobuild']->get($assetName)->dump());
					break;
				case 'HeadScript':
					if(isset($asset['target'])) {
						$renderer->plugin('HeadScript')->appendFile($settings->getPaths()['web'] . '/' . $this->assetManagers['build']->get($assetName)->getTargetPath());
					} else {
						$renderer->plugin('HeadScript')->appendScript($this->assetManagers['nobuild']->get($assetName)->dump());
					}
					break;
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
}
