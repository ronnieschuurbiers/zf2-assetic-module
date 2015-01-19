<?php
namespace ZF2Assetic\View\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;
use ZF2Assetic\Model\Service\AssetHandler;

/**
 * The AssetHelper helps injecting assets into the templates.
 *
 * @author Ronnie Schuurbiers, Pronexus
 * @license https://github.com/magnetronnie/zf2-assetic-module/blob/master/LICENSE
 */
class AssetHelper extends AbstractHelper implements ServiceLocatorAwareInterface {

	protected $serviceLocator;
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator) { $this->serviceLocator = $serviceLocator; }
	public function getServiceLocator() { return $this->serviceLocator; }

	public function __invoke() {
		return $this;
	}

	public function injectAsset($assetCollectionName) {
		/** @var AssetHandler $service */
		$assetHandler = $this->getServiceLocator()->getServiceLocator()->get('ZF2Assetic\AssetHandler');

		return $assetHandler->injectAsset($assetCollectionName, $this->getView());
	}
}