<?php
namespace ZF2Assetic\Model\Listener;


use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\CallbackHandler;
use ZF2Assetic\Model\Service\AssetHandler;
use ZF2Assetic\Model\Service\Settings;

/**
 * The Listener controls the AssetHandler, upon activation by the dispatch event.
 *
 * @author Ronnie Schuurbiers, Pronexus
 * @license https://github.com/magnetronnie/zf2-assetic-module/blob/master/LICENSE
 */
class Listener implements ListenerAggregateInterface
{

	/** @var CallbackHandler[] */
	protected $listeners = array();


	function __construct($serviceManager) {
		$this->serviceManager = $serviceManager;
	}

	/**
	 * {@inheritDoc}
	 */
	public function attach(EventManagerInterface $events) {
		$this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'handleAssets'), -10000);
	}

	/**
	 * {@inheritDoc}
	 */
	public function detach(EventManagerInterface $events) {
		foreach ($this->listeners as $index => $listener) {
			if ($events->detach($listener)) {
				unset($this->listeners[$index]);
			}
		}
	}

	public function handleAssets(MvcEvent $e) {
		$sm     = $e->getApplication()->getServiceManager();

		$response = $e->getResponse();
		if (!$response) {
			$response = new Response();
			$e->setResponse($response);
		}

		/** @var AssetHandler $asseticService */
		$asseticService = $sm->get('ZF2Assetic\AssetHandler');
		/** @var Settings $settings */
		$settings = $sm->get('ZF2Assetic\Settings');

		if(!$settings->getDisabled()) {
			$asseticService->cleanup($settings);

			$asseticService->buildAssets($settings);

			$asseticService->writeAssets($settings);

			// Init assets for modules
			$asseticService->injectAssets($settings, $sm->get('ViewRenderer'));
		}
	}
}
