<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source;

use ArrayIterator\Rev\Source\Events\EventsManager;
use ArrayIterator\Rev\Source\Events\EventsManagerInterface;
use ArrayIterator\Rev\Source\Events\Manager;
use ArrayIterator\Rev\Source\Handlers\ErrorHandler;
use ArrayIterator\Rev\Source\Handlers\Interfaces\ErrorHandlerInterface;
use ArrayIterator\Rev\Source\Handlers\Interfaces\NotFoundHandlerInterface;
use ArrayIterator\Rev\Source\Handlers\Interfaces\RouteErrorHandlerInterface;
use ArrayIterator\Rev\Source\Handlers\NotFoundHandler;
use ArrayIterator\Rev\Source\Handlers\RouteErrorHandler;
use ArrayIterator\Rev\Source\Http\Interfaces\ResponseEmitterInterface;
use ArrayIterator\Rev\Source\Http\ResponseEmitter;
use ArrayIterator\Rev\Source\Interfaces\BootableInterface;
use ArrayIterator\Rev\Source\Routes\Exceptions\RouteErrorException;
use ArrayIterator\Rev\Source\Routes\Exceptions\RouteNotFoundException;
use ArrayIterator\Rev\Source\Routes\Router;
use ArrayIterator\Rev\Source\Storage\Container;
use ArrayIterator\Rev\Source\Traits\BenchmarkingTrait;
use ArrayIterator\Rev\Source\Traits\ServerRequestFactoryTrait;
use ArrayIterator\Rev\Source\Traits\StreamFactoryTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class Application implements BootableInterface
{
    use BenchmarkingTrait,
        StreamFactoryTrait,
        ServerRequestFactoryTrait;

    private ContainerInterface $container;

    private EventsManagerInterface $eventsManager;

    private Router $router;

    private ?LoggerInterface $logger = null;

    private ?NotFoundHandlerInterface $notFoundHandler = null;

    private ?RouteErrorHandlerInterface $routeErrorHandler = null;

    private ?ErrorHandlerInterface $errorHandler = null;

    private ?ResponseEmitter $responseEmitter = null;

    /**
     * @var ?ResponseInterface
     */
    private ?ResponseInterface $response = null;
    /**
     * @var ?ServerRequestInterface
     */
    private ?ServerRequestInterface $request = null;

    /**
     * @var ?ServerRequestInterface
     */
    private ?ServerRequestInterface $defaultRequest = null;

    private bool $prepared = false;

    private bool $dispatched = false;

    private bool $shutdown = false;

    private bool $booted = false;

    private bool $exception = false;

    private float $initialTime;

    private ?float $initialDispatchedTime = null;

    private ?float $initialRunTime = null;

    private ?StreamInterface $previousBuffer = null;

    public function __construct(
        ?ContainerInterface $container = null,
        ?EventsManager $eventsManager = null,
        ?Router $router = null
    ) {
        set_exception_handler([$this, 'handleException']);

        $this->initialTime = microtime(true);
        if ($router) {
            $container ??= $router->getContainer();
            $eventsManager ??= $router->getEventsManager();
        }

        $container ??= new Container();
        $eventsManager ??= new EventsManager();

        $router ??= new Router(container: $container, eventsManager: $eventsManager);
        if (!$router->getEventsManager()) {
            $router->setEventsManager($eventsManager);
        }
        if (!$router->getContainer()) {
            $router->setContainer($container);
        }
        $this->setContainer(container: $container);
        $this->setEventsManager(eventsManager: $eventsManager??Manager::getEventsManager());
        $this->setRouter(router: $router);
    }

    /**
     * @return bool
     */
    public function isException(): bool
    {
        return $this->exception;
    }

    public function handleException(Throwable $e): void
    {
        $this->exception = true;
        $this->response = $this->getErrorHandler()(
            $this,
            $e,
            $this->getRequest()
        );
        $this->emitResponse($this->response);
        $this->shutdown();
    }

    /**
     * @return float
     * @noinspection PhpUnused
     */
    public function getInitialTime(): float
    {
        return $this->initialTime;
    }

    /**
     * @return float|null
     * @noinspection PhpUnused
     */
    public function getInitialDispatchedTime(): ?float
    {
        return $this->initialDispatchedTime;
    }

    /**
     * @return ?float
     * @noinspection PhpUnused
     */
    public function getInitialRunTime(): ?float
    {
        return $this->initialRunTime;
    }

    /**
     * @return ?LoggerInterface
     */
    public function getLogger(): ?LoggerInterface
    {
        if (!$this->logger) {
            try {
                $logger = $this->getContainer()->has(LoggerInterface::class)
                    ? $this->getContainer()->get(LoggerInterface::class)
                    : null;
                if ($logger instanceof LoggerInterface) {
                    $this->setLogger($logger);
                }
            } catch (Throwable) {
            }
        }

        return $this->logger;
    }

    /**
     * @param LoggerInterface|null $logger
     */
    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
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
     * @return EventsManagerInterface
     */
    public function getEventsManager(): EventsManagerInterface
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
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * @param Router $router
     */
    public function setRouter(Router $router): void
    {
        $this->router = $router;
    }

    /**
     * @return NotFoundHandlerInterface|null
     */
    public function getNotFoundHandler(): ?NotFoundHandlerInterface
    {
        if (!$this->notFoundHandler) {
            $this->setNotFoundHandler(new NotFoundHandler());
        }
        return $this->notFoundHandler;
    }

    /**
     * @param NotFoundHandlerInterface $notFoundHandler
     */
    public function setNotFoundHandler(NotFoundHandlerInterface $notFoundHandler): void
    {
        $this->notFoundHandler = $notFoundHandler;
    }

    /**
     * @return ErrorHandlerInterface
     */
    public function getErrorHandler(): ErrorHandlerInterface
    {
        if (!$this->errorHandler) {
            $this->setErrorHandler(new ErrorHandler());
        }
        return $this->errorHandler;
    }

    /**
     * @param ErrorHandlerInterface $errorHandler
     */
    public function setErrorHandler(ErrorHandlerInterface $errorHandler): void
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * @return RouteErrorHandlerInterface
     */
    public function getRouteErrorHandler(): RouteErrorHandlerInterface
    {
        if (!$this->routeErrorHandler) {
            $this->setRouteErrorHandler(new RouteErrorHandler());
        }

        return $this->routeErrorHandler;
    }

    /**
     * @param RouteErrorHandlerInterface $routeErrorHandler
     */
    public function setRouteErrorHandler(RouteErrorHandlerInterface $routeErrorHandler): void
    {
        $this->routeErrorHandler = $routeErrorHandler;
    }

    /**
     * @return ResponseEmitter
     */
    public function getResponseEmitter() : ResponseEmitter
    {
        if (!$this->responseEmitter) {
            try {
                $emitter = $this->getContainer()->has(ResponseEmitterInterface::class)
                    ? $this->getContainer()->get(ResponseEmitterInterface::class)
                    : null;
            } catch (Throwable) {
                $emitter = new ResponseEmitter();
            }
            if (!$emitter instanceof ResponseEmitterInterface) {
                $emitter = new ResponseEmitter();
            }
            $this->setResponseEmitter($emitter);
        }

        $newEmitter = $this->getEventsManager()->dispatch(
            'application.responseEmitter',
            $this->responseEmitter,
            $this
        );

        return $newEmitter instanceof ResponseEmitterInterface
            ? $newEmitter
            : $this->responseEmitter;
    }

    /**
     * @param ResponseEmitter $responseEmitter
     */
    public function setResponseEmitter(ResponseEmitter $responseEmitter): void
    {
        $this->responseEmitter = $responseEmitter;
    }

    /**
     * @return ?StreamInterface
     */
    public function getPreviousBuffer(): ?StreamInterface
    {
        return $this->previousBuffer;
    }

    /**
     * @return ?ResponseInterface
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * @return ?ServerRequestInterface
     */
    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @return ?ServerRequestInterface
     */
    public function getDefaultRequest(): ?ServerRequestInterface
    {
        return $this->defaultRequest;
    }

    /**
     * @param ?ServerRequestInterface $defaultRequest
     */
    public function setDefaultRequest(?ServerRequestInterface $defaultRequest): void
    {
        $this->defaultRequest = $defaultRequest;
    }

    /**
     * @return bool
     */
    public function isDispatched(): bool
    {
        return $this->dispatched;
    }

    /**
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * @return bool
     */
    public function isShutdown(): bool
    {
        return $this->shutdown;
    }

    public function prepare() : static
    {
        if ($this->prepared) {
            return $this;
        }

        $this->prepared = true;
        $this
            ->getEventsManager()
            ->dispatch('on.application.prepare', $this);
        return $this;
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->response) {
            return $this->response;
        }

        // @benchmark
        $benchmark = $this->benchmarkStart(name: 'dispatch', context: [
            'request' => $request
        ], group: 'application');

        if ($this->isDispatched()) {
            throw new RuntimeException(
                'Application has been dispatched, but response does not exists.'
            );
        }

        // @set(request)
        $this->request = $request;

        // @prepare
        $this->prepare();

        $this->initialDispatchedTime = microtime(true);
        $this->initialRunTime ??= $this->initialTime;

        $eventsManager = $this->getEventsManager();
        $newRequest = $eventsManager->dispatch(
            'on.application.dispatch',
            $request,
            $this
        );

        if ($newRequest instanceof ServerRequestInterface) {
            $request = $newRequest;
        }
        // @set(request)
        $this->request = $request;
        $router = $this->getRouter();
        try {
            try {
                $this->response = $router->handle($request);
            } catch (RouteNotFoundException $e) {
                $this->request = $router->getDispatchedRequest()??$this->request;
                $this->response = $this->getNotFoundHandler()($this, $e);
            } catch (RouteErrorException $e) {
                $this->request = $router->getDispatchedRequest()??$this->request;
                $this->response = $this->getRouteErrorHandler()($this, $e);
            }
        } catch (Throwable $e) {
            $this->request = $router->getDispatchedRequest()??$this->request;
            $this->response = $this->getErrorHandler()($this, $e, $router->getDispatchedRequest());
        }

        $response = $eventsManager->dispatch(
            'on.application.dispatched',
            $this->response,
            $this
        );

        // @set(response)
        if ($response instanceof ResponseInterface) {
            $this->response = $response;
        }

        $benchmark->stop([
            'request' => $this->request
        ]);

        return $this->response;
    }

    protected function emitResponse(ResponseInterface $response): void
    {
        $eventsManager = $this->getEventsManager();
        $emitter = $this->getResponseEmitter();
        $reduceError = $eventsManager
            ->dispatch(
                'application.emitter.reduceError',
                true,
                $emitter,
                $response,
                $this
            );

        $eventsManager->dispatch(
            'on.application.emit',
            $response,
            $this
        );

        $reduceError = is_bool($reduceError) ? $reduceError : true;
        if (!$emitter->hasEmitted()) {
            $emitter->emit($response, $reduceError);
        }

        $eventsManager->dispatch(
            'on.application.emitted',
            $response,
            $this
        );
    }

    /**
     * @param ServerRequestInterface|null $request
     * @return $this
     */
    public function boot(?ServerRequestInterface $request = null): static
    {
        if ($this->isBooted()) {
            return $this;
        }

        $request ??= $this->request
            ??$this->getDefaultRequest()
            ??$this->getServerRequestFromGlobals();
        $this->request = $request;
        // @start
        $benchmark = $this->benchmarkStart(
            name: 'boot',
            context: [
                'request' => $this->request
            ],
            group: 'application'
        );

        $this->booted = true;
        $this->initialRunTime = microtime(true);
        $eventsManager = $this->getEventsManager();
        $request = $eventsManager->dispatch(
            'on.application.boot',
            $this->request,
            $this
        );
        if ($request instanceof ServerRequestInterface) {
            $this->request = $request;
        }

        // start buffering
        $level = ob_get_level();
        ob_start();
        $response = $this->dispatch($this->request);
        if (ob_get_length()) {
            $this->previousBuffer = $this->getStreamFactory()->createStream();
            while (ob_get_level() > 0 && ob_get_length()) {
                $this->previousBuffer->write(ob_get_clean());
            }
            if ($level > ob_get_level()) {
                ob_start();
            }
        } elseif (ob_get_level() > $level) {
            ob_end_flush();
        }

        $newResponse = $eventsManager->dispatch(
            'on.application.booted',
            $response,
            $this
        );

        if ($newResponse instanceof ResponseInterface) {
            $response = $newResponse;
        }

        // @emit
        $this->emitResponse($response);

        // @stop
        $benchmark->stop(['request' => $this->request]);

        return $this;
    }

    public function shutdown(): static
    {
        if ($this->isShutdown()) {
            return $this;
        }

        if (!$this->isBooted() && ! $this->isException()) {
            throw new RuntimeException(
                'Application has not been booted.'
            );
        }

        // @start
        $benchmark = $this->benchmarkStart(
            name: 'shutdown',
            context: [],
            group: 'application'
        );
        $this->shutdown = true;
        $emitter = $this->getResponseEmitter();
        $this->getEventsManager()->dispatch('on.application.shutdown');
        $benchmark->stop();
        $emitter->closeConnection();
        return $this;
    }

    public function __destruct()
    {
        if ($this->isBooted()) {
            $this->shutdown();
        }
    }
}
