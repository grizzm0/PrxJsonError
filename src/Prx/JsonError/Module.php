<?php
namespace Prx\JsonError;

use Zend\Mvc\MvcEvent;

/**
 * Class Module
 * @package Prx\JsonError
 */
class Module
{
    /**
     * @return array
     */
    public function getConfig()
    {
        return require(__DIR__ . '/../../../config/module.config.php');
    }

    /**
     * @param MvcEvent $mvcEvent
     */
    public function onBootstrap(MvcEvent $mvcEvent)
    {
        /** @var \Zend\EventManager\EventManagerInterface $eventManager */
        $eventManager   = $mvcEvent->getTarget()->getEventManager();
        $serviceManager = $mvcEvent->getApplication()->getServiceManager();
        $eventManager->attach($serviceManager->get('Prx\JsonError\Listener\JsonErrorListener'));
    }
}
