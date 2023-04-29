<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Traits;

use ArrayIterator\Rev\Source\Http\Factory\ServerRequestFactory;
use ArrayIterator\Rev\Source\Http\ServerRequest;
use ArrayIterator\Rev\Source\Http\Stream;
use ArrayIterator\Rev\Source\Http\Uri;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

trait ServerRequestFactoryTrait
{
    abstract public function getContainer() : ?ContainerInterface;

    public function getServerRequestFactory() : ServerRequestFactory
    {
        $container = $this->getContainer();
        try {
            $factory = $container?->has(ServerRequestFactoryInterface::class)
                ? $container->get(ServerRequestFactoryInterface::class)
                : null;
        } catch (Throwable) {
            $factory = new ServerRequestFactory();
        }
        if (!$factory instanceof ResponseFactoryInterface) {
            $factory = new ServerRequestFactory();
        }
        return $factory;
    }

    public function getServerRequestFromGlobals(): ServerRequestInterface
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri      = Uri::fromGlobals();
        $body     = Stream::fromFile('php://input', 'r+');
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';
        $serverRequest = $this
            ->getServerRequestFactory()
            ->createServerRequest($method, $uri, $_SERVER)
            ->withParsedBody($body)
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withProtocolVersion($protocol)
            ->withUploadedFiles(ServerRequest::normalizeFiles($_FILES));
        $headers = getallheaders();
        if (!is_array($headers)) {
            $headers = [];
            foreach ($_SERVER as $key => $value) {
                if (str_starts_with($key, 'HTTP_')) {
                    $key = substr($key, 5);
                } elseif ($key !== 'CONTENT_LENGTH' && $key !== 'CONTENT_TYPE') {
                    continue;
                }
                $key = ucwords(strtolower(str_replace('_', '-', $key)), '-');
                $headers[$key] = $value;
            }
        }
        foreach ($headers as $key => $header) {
            $serverRequest = $serverRequest->withHeader($key, $header);
        }
        return $serverRequest;
    }
}
