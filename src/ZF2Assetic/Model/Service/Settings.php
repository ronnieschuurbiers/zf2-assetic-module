<?php
namespace ZF2Assetic\Model\Service;

use Zend\Stdlib;

/**
 * The Settings object provides access to the module's configuration.
 *
 * @author Ronnie Schuurbiers, Pronexus
 * @license https://github.com/magnetronnie/zf2-assetic-module/blob/master/LICENSE
 */
class Settings {

	protected $debug = false;
	protected $allowOverwrite = true;
	protected $cache = false;
	protected $cacheBusting = null;
	protected $cleanUp = false;
	protected $paths = array();
	protected $assets = array();


	public function getDebug() { return $this->debug; }
	public function setDebug($flag) { $this->debug = (bool) $flag; }

	public function getAllowOverwrite() { return $this->allowOverwrite; }
	public function setAllowOverwrite($flag) { $this->allowOverwrite = (bool) $flag; }

	public function getCache() { return $this->cache; }
	public function setCache($flag) { $this->cache = (bool) $flag; }

	public function getCacheBusting() { return $this->cacheBusting; }
	public function setCacheBusting($cacheBusting) { $this->cacheBusting = $cacheBusting; }

	public function getCleanUp() { return $this->cleanUp; }
	public function setCleanUp($flag) { $this->cleanUp = (bool) $flag; }

	public function getFilters() { return $this->filters; }
	public function setFilters(array $filters) { $this->filters = $filters; }

	public function getPaths() { return $this->paths; }
	public function setPaths(array $paths) { $this->paths = $paths; }

	public function getAssets() { return $this->assets; }
	public function setAssets(array $assets) { $this->assets = $assets; }


	public function __construct($config = null) {
		if (null !== $config) {
			if (is_array($config)) {
				$this->processConfig($config);
			} elseif ($config instanceof \Traversable) {
				$this->processConfig(Stdlib\ArrayUtils::iteratorToArray($config));
			} else {
				throw new \Exception(
					'Parameter to \\ZF2Assetic\\Settings\'s '
					. 'constructor must be an array or implement the '
					. '\\Traversable interface'
				);
			}
		}
	}

	protected function processConfig($config) {
		foreach ($config as $key => $value) {
			$setter = 'set' . ucfirst($key);
			$this->{$setter}($value);
		}
	}
}
