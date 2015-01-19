<?php
namespace ZF2Assetic\Model\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF2Assetic\Model\Service\Settings;

/**
 * The SettingsFactory creates a Settings instance.
 *
 * @author Ronnie Schuurbiers, Pronexus
 * @license https://github.com/magnetronnie/zf2-assetic-module/blob/master/LICENSE
 */
class SettingsFactory implements FactoryInterface {

	public function createService(ServiceLocatorInterface $serviceLocator) {
		$config = $serviceLocator->get('Configuration');
		$settings = new Settings($config['zf2assetic']);
		return $settings;
	}
}