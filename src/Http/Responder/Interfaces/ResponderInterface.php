<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Responder\Interfaces;

use Psr\Http\Message\ResponseInterface;

interface ResponderInterface
{
    public function setContentType(string $contentType);

    public function getContentType() : string;

    public function serve(int $code, $data, ResponseInterface $response) : ResponseInterface;
}
