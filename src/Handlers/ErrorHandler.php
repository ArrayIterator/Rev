<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Handlers;

use ArrayIterator\Rev\Source\Application;
use ArrayIterator\Rev\Source\Handlers\Interfaces\ErrorHandlerInterface;
use ArrayIterator\Rev\Source\Handlers\Traits\ErrorHandlerTraits;
use ArrayIterator\Rev\Source\Http\Code;
use ArrayIterator\Rev\Source\Http\Exceptions\NotFoundException;
use ArrayIterator\Rev\Source\Routes\Exceptions\RouteErrorException;
use ArrayIterator\Rev\Source\Traits\ServerRequestFactoryTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LogLevel;
use Throwable;

class ErrorHandler implements ErrorHandlerInterface
{
    use ErrorHandlerTraits,
        ServerRequestFactoryTrait;

    protected int $httpCode = 500;

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function __invoke(
        Application $application,
        Throwable $exception,
        ?ServerRequestInterface $request = null
    ): ResponseInterface {
        if (!$this->logger) {
            $this->setLogger($application->getLogger());
        }

        if ($application
            ->getEventsManager()
            ->dispatch(
                'error.log.exception',
                true
            ) === true
        ) {
            $this->log(
                LogLevel::ERROR,
                $exception,
                [
                    'exception_type' => 'exception'
                ]
            );
        }

        $code = 500;
        if ($exception instanceof NotFoundException) {
            $code = 400;
        } elseif ($exception instanceof RouteErrorException) {
            // fallback code
            $code = Code::statusMessage($exception->getCode()) ? $exception->getCode() : 500;
            // if code below 400 will be fallback to 500
            if ($code < 400) {
                $code = 500;
            }
        }

        $request ??= $application->getRequest()??$this->getServerRequestFromGlobals();
        $this->httpCode = $code;
        $this->setContainer($application->getContainer());
        $this->setEventsManager($application->getEventsManager());
        $factory = $this->getResponseFactory();
        $response = $factory->createResponse($code);
        return $this->render($exception, $request, $response);
    }
}
