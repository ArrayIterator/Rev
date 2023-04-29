<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Routes;

use ArrayIterator\Rev\Source\Events\Manager;
use ArrayIterator\Rev\Source\Http\Responder\Interfaces\JsonResponderInterface;
use ArrayIterator\Rev\Source\Http\Responder\Json;
use ArrayIterator\Rev\Source\Routes\Exceptions\RouteMethodNotExistsException;
use ArrayIterator\Rev\Source\Traits\ResponseFactoryTrait;
use JsonSerializable;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use ReflectionMethod;
use Throwable;

abstract class Controller
{
    use ResponseFactoryTrait;

    private bool $dispatched = false;

    protected ResponseInterface $response;

    protected ?ContainerInterface $container;

    final public function __construct(public readonly Router $router)
    {
        $this->container = $this->router->getContainer();
    }

    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    protected function setResponseAsJson(): static
    {
        $this->response = $this->response->withHeader('Content-Type', 'application/json');
        return $this;
    }

    /**
     * @return bool
     */
    final public function isDispatched(): bool
    {
        return $this->dispatched;
    }

    /**
     * @return ResponseInterface
     * @noinspection PhpUnused
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    protected function getJsonResponder()
    {
        $container = $this->router->getContainer();
        try {
            $json = $container?->has(JsonResponderInterface::class)
                ? $container->get(JsonResponderInterface::class)
                : null;
        } catch (Throwable) {
            $json = new Json($container, $this->router->getEventsManager()??Manager::getEventsManager());
        }
        if (!$json instanceof JsonResponderInterface) {
            $json = new Json($container, $this->router->getEventsManager()??Manager::getEventsManager());
        }

        return $json;
    }

    final public function dispatch(
        string $method,
        ...$arguments
    ): ResponseInterface {
        if ($this->isDispatched()) {
            return $this->response;
        }

        $this->response = $this->getResponseFactory()->createResponse();
        $this->dispatched = true;
        if (!method_exists($this, $method)) {
            throw new RouteMethodNotExistsException(
                $this->router,
                $this,
                $method
            );
        }
        $eventsManager = $this->router->getEventsManager();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $response = $this->beforeMapping($method, $arguments);

        // call events
        $response = $eventsManager
            ->dispatch(
                'controller.beforeMappingResponse',
                $response,
                $this
            );

        if (!is_string($response)
            && !$response instanceof ResponseInterface
            && !$response instanceof StreamInterface
            && !$response instanceof JsonSerializable
        ) {
            $method = new ReflectionMethod($this, $method);
            if ($method->isPrivate()) {
                $response = (function ($method, ...$arguments) {
                    return $this->$method(...$arguments);
                })->call($this, $method->getName(), ...$arguments);
            } else {
                $response = $this->{$method->getName()}(...$arguments);
            }
        }

        if ($response instanceof ResponseInterface) {
            $this->response = $response;
        } else {
            $is_json = is_array($response) || (bool) preg_match(
                '~^application/(?:[^+]+\+)?json\s*($|;)~i',
                $this->response->getHeaderLine('Content-Type')
            );

            if (!$is_json && (
                    $response instanceof JsonSerializable
                    || (
                        is_array($response)
                        && $eventsManager
                            ->dispatch('controller.arrayAsJson', false) === true
                    )
                )
            ) {
                $this->setResponseAsJson();
                $is_json = true;
            }

            if ($is_json && (
                is_array($response) || $response instanceof JsonSerializable
            )) {
                $response = $this
                    ->getJsonResponder()
                    ->serve(
                        $this->response->getStatusCode(),
                        $response,
                        $this->response
                    );
            }

            if (is_string($response)) {
                $this->response->getBody()->write($response);
            }

            if ($response instanceof StreamInterface) {
                $response = $this->response->withBody($response);
            }
            if ($response instanceof ResponseInterface) {
                $this->response = $response;
            }
        }

        $response = $eventsManager
            ->dispatch(
                'controller.dispatchResponse',
                $response,
                $this
            );
        if ($response instanceof ResponseInterface) {
            $this->response = $response;
        }

        return $this->response;
    }

    /**
     * Method called before route match called.
     * This method should be overridden when controller need to stop the method execution.
     * To stop execution the return values must be:
     * 1. string
     * 2. @uses ResponseInterface
     * 3. returning array|@uses JsonSerializable and response content type need to be:
     *  regex: /^application/(?:[^+]+\+)?json\s*($|;)/
     *
     * @param string $method
     * @param array $arguments
     */
    protected function beforeMapping(string $method, array $arguments)
    {
    }
}
