<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Factory;

use ArrayIterator\Rev\Source\Http\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, reason: $reasonPhrase);
    }
}
