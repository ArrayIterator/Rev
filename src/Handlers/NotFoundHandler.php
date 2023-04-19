<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Handlers;

use ArrayIterator\Rev\Source\Application;
use ArrayIterator\Rev\Source\Handlers\Interfaces\NotFoundHandlerInterface;
use ArrayIterator\Rev\Source\Handlers\Traits\ErrorHandlerTraits;
use ArrayIterator\Rev\Source\Routes\Exceptions\RouteNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;

class NotFoundHandler implements NotFoundHandlerInterface
{
    use ErrorHandlerTraits;

    public function getHttpCode(): int
    {
        return 404;
    }

    public function __invoke(
        Application $application,
        RouteNotFoundException $exception
    ): ResponseInterface {
        if (!$this->logger) {
            $this->setLogger($application->getLogger());
        }
        if ($application
                ->getEventsManager()
                ->dispatch(
                    'error.log.notfound',
                    true
                ) === true
        ) {
            $this->log(
                LogLevel::NOTICE,
                $exception,
                [
                    'exception_type' => 'route_not_found'
                ]
            );
        }

        $this->setContainer($application->getContainer());
        $this->setEventsManager($application->getEventsManager());
        $request = $exception->getRequest();
        $factory = $this->getResponseFactory();
        $response = $factory->createResponse(404);
        return $this->render($exception, $request, $response);
    }
}
