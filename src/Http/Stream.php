<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http;

use ArrayIterator\Rev\Source\Http\Exceptions\FileNotFoundException;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;
use const PHP_VERSION_ID;

class Stream implements StreamInterface
{
    /**
     * @see http://php.net/manual/function.fopen.php
     * @see http://php.net/manual/en/function.gzopen.php
     */
    const READABLE_MODES = '~r|a\+|ab\+|w\+|wb\+|x\+|xb\+|c\+|cb\+~';

    const WRITABLE_MODES = '~a|w|r\+|rb\+|rw|x|c~';

    /**
     * @var resource
     */
    private $stream;

    /**
     * @var ?int
     */
    private ?int $size = null;

    /**
     * @var array
     */
    private array $metadata;

    /**
     * @var array
     */
    private array $customMetadata;

    /**
     * @var bool
     */
    private bool $seekable;

    /**
     * @var bool
     */
    private bool $readable;

    /**
     * @var bool
     */
    private bool $writable;

    /**
     * @var ?string
     */
    private ?string $uri;

    /**
     * @param resource $stream
     */
    public function __construct($stream, array $options = [])
    {
        $this->assertIsResource($stream);
        $this->stream   = $stream;
        $this->customMetadata = $options;
        $this->metadata = stream_get_meta_data($this->stream);
        $this->readable = (bool)preg_match(
            self::READABLE_MODES,
            $this->metadata['mode']
        );
        $this->writable = (bool)preg_match(
            self::WRITABLE_MODES,
            $this->metadata['mode']
        );
        $this->seekable = (bool) $this->metadata['seekable'];
        if (isset($options['size']) && is_int($options['size']) && $options['size'] >= 0) {
            $this->size = $options['size'];
        }
        $this->uri = $this->getMetadata('uri');
    }

    /**
     * @param mixed $stream
     * @throws InvalidArgumentException
     */
    private function assertIsResource(mixed $stream)
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException(
                'Stream must be a resource'
            );
        }
    }

    /**
     * @throws RuntimeException
     */
    private function assertDetachedResource($stream): void
    {
        if (!$stream || !is_resource($stream)) {
            throw new RuntimeException(
                'Stream is detached'
            );
        }
    }

    /**
     * @param string $fileName
     * @param string $mode
     *
     * @return static
     */
    public static function fromFile(string $fileName, string $mode = 'r') : static
    {
        if (!preg_match('~^php://(fd|filter|memory|temp|std(in|out|err)|(in|out)put)~i', $fileName)
            && !is_file($fileName)
        ) {
            throw new FileNotFoundException($fileName);
        }
        return new static(fopen($fileName, $mode));
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        try {
            if ($this->isSeekable()) {
                $this->seek(0);
            }
            return $this->getContents();
        } catch (Throwable $e) {
            if (PHP_VERSION_ID >= 70400) {
                throw $e;
            }
            throw new RuntimeException(
                sprintf(
                    '%s::__toString exception: %s',
                    static::class,
                    (string) $e
                ),
                E_USER_ERROR
            );
        }
    }

    public function close()
    {
        if (!$this->stream) {
            return;
        }
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->detach();
    }

    public function detach()
    {
        if (!$this->stream) {
            return null;
        }

        $result = $this->stream;
        $this->stream =
        $this->size =
        $this->uri = null;
        $this->seekable = $this->readable = $this->writable = false;

        return $result;
    }

    public function getSize() : ?int
    {
        if ($this->size !== null) {
            return $this->size;
        }

        if (!$this->stream) {
            return null;
        }

        // Clear the stat cache if the stream has a URI
        if ($this->uri) {
            clearstatcache(true, $this->uri);
        }

        $stats = fstat($this->stream);
        if (is_array($stats) && isset($stats['size'])) {
            $this->size = $stats['size'];
            return $this->size;
        }

        return null;
    }

    public function tell() : int
    {
        $this->assertDetachedResource($this->stream);
        $result = ftell($this->stream);
        if ($result === false) {
            throw new RuntimeException(
                'Unable to determine stream position'
            );
        }

        return $result;
    }

    public function eof() : bool
    {
        $this->assertDetachedResource($this->stream);
        return feof($this->stream);
    }

    /**
     * @return bool
     */
    public function isSeekable() : bool
    {
        return $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        $this->assertDetachedResource($this->stream);
        if (!$this->seekable) {
            throw new RuntimeException('Stream is not seekable');
        }

        $result = fseek($this->stream, $offset);
        if ($result === -1) {
            throw new RuntimeException('Unable to determine stream position');
        }
    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function isWritable() : bool
    {
        return $this->writable;
    }

    public function write($string) : int
    {
        $this->assertDetachedResource($this->stream);

        if (!$this->writable) {
            throw new RuntimeException(
                'Cannot write to a non-writable stream'
            );
        }

        // We can't know the size after writing anything
        $this->size = null;
        $result = fwrite($this->stream, $string);
        if ($result === false) {
            throw new RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    public function isReadable() : bool
    {
        return $this->readable;
    }

    public function read($length) : string
    {
        $this->assertDetachedResource($this->stream);
        if (!$this->readable) {
            throw new RuntimeException(
                'Cannot read from non-readable stream'
            );
        }
        if ($length < 0) {
            throw new RuntimeException(
                'Length parameter cannot be negative'
            );
        }
        if (0 === $length) {
            return '';
        }

        try {
            $string = fread($this->stream, $length);
        } catch (Exception $e) {
            throw new RuntimeException(
                'Unable to read from stream',
                0,
                $e
            );
        }

        if (false === $string) {
            throw new RuntimeException('Unable to read from stream');
        }

        return $string;
    }

    private function assertContent($content) : string
    {
        if ($content === false) {
            throw new RuntimeException('Unable to read stream contents');
        }

        return $content;
    }

    public function getContents() : string
    {
        $this->assertDetachedResource($this->stream);
        if (!$this->readable) {
            throw new RuntimeException(
                'Cannot read from non-readable stream'
            );
        }

        $ex = null;
        set_error_handler(static function (int $errno, string $errstr) use (&$ex) : bool {
            $ex = new RuntimeException(sprintf(
                'Unable to read stream contents: %s',
                $errstr
            ));

            return true;
        });

        try {
            return $this->assertContent(stream_get_contents($this->stream));
        } catch (Throwable $e) {
            $ex = new RuntimeException(sprintf(
                'Unable to read stream contents: %s',
                $e->getMessage()
            ), 0, $e);
        } finally {
            restore_error_handler();
        }
        throw $ex;
    }

    /**
     * @param ?string $key
     *
     * @return mixed
     */
    public function getMetadata($key = null) : mixed
    {
        if (!$this->stream) {
            return $key ? null : [];
        } elseif (!$key) {
            return $this->customMetadata + stream_get_meta_data($this->stream);
        } elseif (isset($this->customMetadata[$key])) {
            return $this->customMetadata[$key];
        }

        $meta = stream_get_meta_data($this->stream);

        return $meta[$key] ?? null;
    }

    public function __destruct()
    {
        $this->close();
    }
}
