<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Handlers\Traits;

use ArrayIterator\Rev\Source\Events\EventsManagerInterface;
use ArrayIterator\Rev\Source\Events\Manager;
use ArrayIterator\Rev\Source\Http\Factory\ResponseFactory;
use ArrayIterator\Rev\Source\Http\Responder\Interfaces\JsonResponderInterface;
use ArrayIterator\Rev\Source\Http\Responder\Json;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Stringable;
use Throwable;

trait ErrorHandlerTraits
{
    private bool $displayError = true;

    private bool $logging = true;

    private ?LoggerInterface $logger = null;

    private ?ContainerInterface $container = null;

    private ?EventsManagerInterface $eventsManager = null;

    public function __construct(
        bool $displayError = true,
        bool $logging = true
    ) {
        $this->displayError = $displayError;
        $this->logging = $logging;
    }

    public function getHttpCode() : int
    {
        return 0;
    }

    /**
     * @return ContainerInterface|null
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @return EventsManagerInterface|null
     */
    public function getEventsManager(): ?EventsManagerInterface
    {
        return $this->eventsManager;
    }

    /**
     * @param EventsManagerInterface $eventsManager
     */
    public function setEventsManager(EventsManagerInterface $eventsManager): void
    {
        $this->eventsManager = $eventsManager;
    }

    /**
     * @return bool
     */
    public function isDisplayError(): bool
    {
        return $this->displayError;
    }

    /**
     * @param bool $displayError
     */
    public function setDisplayError(bool $displayError): void
    {
        $this->displayError = $displayError;
    }

    /**
     * @return bool
     */
    public function isLogging(): bool
    {
        return $this->logging;
    }

    /**
     * @param bool $logging
     */
    public function setLogging(bool $logging): void
    {
        $this->logging = $logging;
    }

    /**
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface|null $logger
     */
    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    protected function log(string $level, string|Stringable $message, array $context = []): void
    {
        $this->getLogger()?->log($level, $message, $context);
    }

    protected function getResponseFactory() : ResponseFactoryInterface
    {
        $container = $this->getContainer();
        try {
            $factory = $container?->has(ResponseFactoryInterface::class)
                ? $container->get(ResponseFactoryInterface::class)
                : null;
        } catch (Throwable) {
            $factory = new ResponseFactory();
        }
        if (!$factory instanceof ResponseFactoryInterface) {
            $factory = new ResponseFactory();
        }
        return $factory;
    }

    protected function getJsonResponder()
    {
        $container = $this->getContainer();
        try {
            $json = $container?->has(JsonResponderInterface::class)
                ? $container->get(JsonResponderInterface::class)
                : null;
        } catch (Throwable) {
            $json = new Json($container, $this->getEventsManager()??Manager::getEventsManager());
        }
        if (!$json instanceof JsonResponderInterface) {
            $json = new Json($container, $this->getEventsManager()??Manager::getEventsManager());
        }

        return $json;
    }

    protected function render(
        Throwable $exception,
        ServerRequestInterface $request,
        ResponseInterface $response
    ) : ResponseInterface {
/*
        $contentType = strtolower($request->getHeaderLine('Content-Type'));

        if (preg_match('~/(.+)?json~', $contentType)) {
        }
*/

        $responder = $this->getJsonResponder();
        $code = $this->getHttpCode() === 0
            ? $response->getStatusCode()
            : $this->getHttpCode();
        return $responder->serve(
            $code,
            $exception,
            $response
        );
    }
}
