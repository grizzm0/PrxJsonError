<?php
namespace Prx\JsonError\Listener;

use Exception;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;

/**
 * Class JsonErrorListener
 * @package Prx\JsonError\Listener
 */
class JsonErrorListener extends AbstractListenerAggregate
{
    /**
     * @param EventManagerInterface $eventManager
     */
    public function attach(EventManagerInterface $eventManager)
    {
        $this->listeners[] = $eventManager->attach(MvcEvent::EVENT_RENDER,       [$this, 'convertHtmlToJson']);
        $this->listeners[] = $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, [$this, 'convertHtmlToJson']);
    }

    /**
     * @param MvcEvent $mvcEvent
     */
    public function convertHtmlToJson(MvcEvent $mvcEvent)
    {
        if (!$mvcEvent->isError()) {
            return;
        }

        $request = $mvcEvent->getRequest();
        if (!$request instanceof Request) {
            return;
        }

        $headers = $request->getHeaders();
        if (!$headers->has('Accept')) {
            return;
        }

        /** @var \Zend\Http\Header\Accept $accept */
        $accept = $headers->get('Accept');
        $match  = $accept->match('application/json');
        if (!$match || $match->getTypeString() == '*/*') {
            return;
        }

        /** @var \Zend\View\Model\ViewModel $result */
        $result = $mvcEvent->getResult();
        if ($result instanceof JsonModel) {
            return;
        }

        /** @var \Zend\Http\PhpEnvironment\Response $response */
        $response  = $mvcEvent->getResponse();
        $jsonModel = new JsonModel([
            'error'  => $response->getReasonPhrase(),
            'status' => $response->getStatusCode(),
        ]);

        switch ($result->getVariable('reason', null)) {
            case 'error-controller-cannot-dispatch':
                $jsonModel->setVariable('message', 'The requested controller was unable to dispatch the request.');
                break;
            case 'error-controller-not-found':
                $jsonModel->setVariable('message', 'The requested controller could not be mapped to an existing controller class.');
                break;
            case 'error-controller-invalid':
                $jsonModel->setVariable('message', 'The requested controller was not dispatchable.');
                break;
            case 'error-router-no-match':
                $jsonModel->setVariable('message', 'The requested URL could not be matched by routing.');
                break;
            default:
                $jsonModel->setVariable('message', $result->getVariable('message'));
                break;
        }

        /** @var Exception $exception */
        if ($result->getVariable('display_exceptions', false)) {
            $controller = $result->getVariable('controller', false);
            if ($controller) {
                $controllerMessage = $controller;

                $controllerClass = $result->getVariable('controller_class', false);
                if ($controllerClass && $controllerClass != $controller) {
                    $controllerMessage .= sprintf(' resolves to %s', $controllerClass);
                }

                $jsonModel->setVariable('controller', [
                    'message' => $controllerMessage
                ]);
            }

            /** @var Exception $exception */
            $exception = $result->getVariable('exception', false);
            if ($exception && $exception instanceof Exception) {
                $jsonModel->setVariable('exception', [
                    'class'       => get_class($exception),
                    'line'        => $exception->getFile() .':'. $exception->getLine(),
                    'message'     => $exception->getMessage(),
                    'stack_trace' => $exception->getTraceAsString(),
                ]);
            } else {
                $jsonModel->setVariable('exception', [
                    'message' => 'No exception available'
                ]);
            }
        }

        $mvcEvent->setResult($jsonModel);
        $mvcEvent->setViewModel($jsonModel);
    }
}
