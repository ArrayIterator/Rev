<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Factory;

use ArrayIterator\Rev\Source\Events\Manager;
use ArrayIterator\Rev\Source\Http\Stream;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class StreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface
    {
        $file = Manager::dispatch('default.socketFile', 'php://temp');
        $stream = $this->createStreamFromFile($file, 'r+');
        $content !== '' && $stream->write($content);
        return $stream;
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return Stream::fromFile($filename, $mode);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }
}
