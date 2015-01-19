<?php
namespace ZF2Assetic;

use Zend\Http\Response;
use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use ZF2Assetic\Model\Listener\Listener;

/**
 * @author Ronnie Schuurbiers, Pronexus
 * @license https://github.com/magnetronnie/zf2-assetic-module/blob/master/LICENSE
 */
class Module implements
        AutoloaderProviderInterface,
        ConfigProviderInterface,
        BootstrapListenerInterface
{
    /**
     * Listen to the bootstrap event
     *
     * @param \Zend\EventManager\EventInterface $e
     * @return array
     */
    public function onBootstrap(EventInterface $e)
    {
		$serviceManager = $e->getApplication()->getServiceManager();
		$eventManager = $e->getTarget()->getEventManager();
		$eventManager->attach(new Listener($serviceManager));
    }

    /**
     * Returns configuration to merge with application configuration
     *
     * @return array|\Traversable
     */
    public function getConfig()
    {
        return array_merge(
            include __DIR__ . '/config/module.config.php'
        );
    }

    /**
     * Return an array for passing to Zend\Loader\AutoloaderFactory.
     *
     * @return array
     */
	public function getAutoloaderConfig() {
		return array(
			'Zend\Loader\ClassMapAutoloader' => array(
				__DIR__ . '/autoload_classmap.php',
			),
			'Zend\Loader\StandardAutoloader' => array(
				'namespaces' => array(
					__NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
				),
			),
		);
	}
}
