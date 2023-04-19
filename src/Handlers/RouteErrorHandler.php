<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Handlers;

use ArrayIterator\Rev\Source\Application;
use ArrayIterator\Rev\Source\Handlers\Interfaces\RouteErrorHandlerInterface;
use ArrayIterator\Rev\Source\Handlers\Traits\ErrorHandlerTraits;
use ArrayIterator\Rev\Source\Http\Code;
use ArrayIterator\Rev\Source\Routes\Exceptions\RouteErrorException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;

class RouteErrorHandler implements RouteErrorHandlerInterface
{
    use ErrorHandlerTraits;

    protected int $httpCode = 0;

    /**
     * @return int use
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function __invoke(
        Application $application,
        RouteErrorException $exception
    ): ResponseInterface {
        if (!$this->logger) {
            $this->setLogger($application->getLogger());
        }
        if ($application
                ->getEventsManager()
                ->dispatch(
                    'error.log.route_error',
                    true
                ) === true
        ) {
            $this->log(
                LogLevel::WARNING,
                $exception->getException(),
                [
                    'exception_type' => 'route_error'
                ]
            );
        }

        // fallback code
        $code = Code::statusMessage($exception->getCode()) ? $exception->getCode() : 500;
        // if code below 400 will be fallback to 500
        if ($code < 400) {
            $code = 500;
        }

        $this->httpCode = $code;
        $this->setContainer($application->getContainer());
        $this->setEventsManager($application->getEventsManager());
        $request = $exception->getRequest();
        $factory = $this->getResponseFactory();
        $response = $factory->createResponse($code);
        return $this->render($exception, $request, $response);
    }
}
