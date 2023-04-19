<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http;

use ArrayIterator\Rev\Source\Http\Interfaces\ResponseEmitterInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ResponseEmitter implements ResponseEmitterInterface
{
    private const CONTENT_PATTERN_REGEX = '/(?P<unit>[\w]+)\s+(?P<first>\d+)-(?P<last>\d+)\/(?P<length>\d+|\*)/';

    /**
     * Maximum output buffering size for each iteration.
     */
    protected int $maxBufferLength = 8192;

    /**
     * @var bool
     */
    private bool $emitted = false;
    private bool $closed = false;

    protected function assertNoPreviousOutput(): void
    {
        $file = $line = null;
        if (headers_sent($file, $line)) {
            throw new RuntimeException(sprintf(
                'Unable to emit response: Headers already sent in file %s on line %s. '
                . 'This happens if echo, print, printf, print_r, var_dump, var_export or '
                . 'similar statement that writes to the output buffer are used.',
                $file,
                (string) $line
            ));
        }

        if (ob_get_level() <= 0) {
            return;
        }

        if (ob_get_length() <= 0) {
            return;
        }

        throw new RuntimeException('Output has been emitted previously; cannot emit response.');
    }

    /**
     * Emit the status line.
     *
     * Emits the status line using the protocol version and status code from
     * the response; if a reason phrase is available, it, too, is emitted.
     *
     * It's important to mention that, in order to prevent PHP from changing
     * the status code of the emitted response, this method should be called
     * after `emitBody()`
     */
    protected function emitStatusLine(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        header(
            vsprintf(
                'HTTP/%s %d%s',
                [
                    $response->getProtocolVersion(),
                    $statusCode,
                    rtrim(' ' . $response->getReasonPhrase()),
                ]
            ),
            true,
            $statusCode
        );
    }
    /**
     * Emit response headers.
     *
     * Loops through each header, emitting each; if the header value
     * is an array with multiple values, ensures that each is sent
     * in such a way as to create aggregate headers (instead of replace
     * the previous).
     */
    protected function emitHeaders(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        foreach ($response->getHeaders() as $header => $values) {
            $name = $this->toWordCase($header);
            $first = $name !== 'Set-Cookie';

            foreach ($values as $value) {
                header(
                    sprintf(
                        '%s: %s',
                        $name,
                        $value
                    ),
                    $first,
                    $statusCode
                );

                $first = false;
            }
        }
    }
    /**
     * Converts header names to word-case.
     */
    protected function toWordCase(string $header): string
    {
        $filtered = str_replace('-', ' ', $header);
        $filtered = ucwords($filtered);

        return str_replace(' ', '-', $filtered);
    }

    public function emit(ResponseInterface $response, bool $reduceError = false)
    {
        if ($this->hasEmitted()) {
            return;
        }

        $this->emitted = true;
        if (!$reduceError) {
            $this->assertNoPreviousOutput();
        } elseif (ob_get_length() > 0) {
            $c = 10;
            $cleaned = false;
            while ($c-- > 0 && ob_get_length() > 0 && ob_get_level() > 0) {
                $cleaned = true;
                ob_end_clean();
            }
        }

        $this->emitStatusLine($response);

        $this->emitHeaders($response);

        flush();

        !empty($cleaned) && ob_start();
        $range = $this->parseContentRange($response->getHeaderLine('Content-Range'));

        if ($range !== null && $range[0] === 'bytes') {
            $this->emitBodyRange($range, $response, $this->maxBufferLength);
        } else {
            $this->emitBody($response, $this->maxBufferLength);
        }
    }

    /**
     * Parse content-range header
     * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.16.
     *
     * @psalm-return null|array{0: string, 1: int, 2: int, 3: string|int}
     *     returns null if no content range or an invalid content range is provided
     */
    private function parseContentRange(string $header): ?array
    {
        if (preg_match(self::CONTENT_PATTERN_REGEX, $header, $matches) === 1) {
            return [
                (string) $matches['unit'],
                (int) $matches['first'],
                (int) $matches['last'],
                $matches['length'] === '*' ? '*' : (int) $matches['length'],
            ];
        }

        return null;
    }

    /**
     * Emit a range of the message body.
     *
     * @psalm-param array{0: string, 1: int, 2: int, 3: string|int} $range
     */
    private function emitBodyRange(array $range, ResponseInterface $response, int $maxBufferLength): void
    {
        [/* $unit */, $first, $last, /* $length */] = $range;

        $body = $response->getBody();

        $length = $last - $first + 1;

        if ($body->isSeekable()) {
            $body->seek($first);
            $first = 0;
        }

        if (! $body->isReadable()) {
            echo substr($body->getContents(), $first, $length);

            return;
        }

        $remaining = $length;

        while ($remaining >= $maxBufferLength && ! $body->eof()) {
            $contents = $body->read($maxBufferLength);
            $remaining -= strlen($contents);

            echo $contents;

            if (connection_status() !== CONNECTION_NORMAL) {
                break;
            }
        }

        if ($remaining <= 0) {
            return;
        }

        if ($body->eof()) {
            return;
        }

        echo $body->read($remaining);
    }

    /**
     * Sends the message body of the response.
     */
    private function emitBody(ResponseInterface $response, int $maxBufferLength): void
    {
        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        if (! $body->isReadable()) {
            echo $body;

            return;
        }

        while (! $body->eof()) {
            echo $body->read($maxBufferLength);

            if (connection_status() !== CONNECTION_NORMAL) {
                break;
            }
        }
    }

    public function hasEmitted(): bool
    {
        return $this->emitted;
    }

    public function closeConnection() : void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        if (! in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
            $status = ob_get_status(true);
            $level = count($status);
            $flags = PHP_OUTPUT_HANDLER_REMOVABLE | PHP_OUTPUT_HANDLER_FLUSHABLE;
            $maxBufferLevel = 0;
            while ($level-- > $maxBufferLevel
                && isset($status[$level])
                && ($status[$level]['del']
                    ??(! isset($status[$level]['flags']) || $flags === ($status[$level]['flags'] & $flags))
                )
            ) {
                ob_end_flush();
            }
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }
}
