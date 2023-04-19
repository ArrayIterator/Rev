<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use function getallheaders;

class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @var array
     */
    private array $attributes = [];

    /**
     * @var array
     */
    private array $cookieParams = [];

    /**
     * @var array|object|null
     */
    private array|object|null $parsedBody;

    /**
     * @var array
     */
    private array $queryParams = [];

    /**
     * @var array
     */
    private array $serverParams;

    /**
     * @var array
     */
    private array $uploadedFiles = [];

    /**
     * @param string                               $method       HTTP method
     * @param string|UriInterface                  $uri          URI
     * @param array<string, string|string[]>       $headers      Request headers
     * @param string|resource|StreamInterface|null $body         Request body
     * @param string                               $version      Protocol version
     * @param array                                $serverParams Typically the $_SERVER super global
     */
    public function __construct(
        string $method,
        $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        array $serverParams = []
    ) {
        $this->serverParams = $serverParams;

        parent::__construct($method, $uri, $headers, $body, $version);
    }

    /**
     * Return an UploadedFile instance array.
     *
     * @param array $files An array which respect $_FILES structure
     *
     * @throws InvalidArgumentException for unrecognized values
     */
    public static function normalizeFiles(array $files) : array
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;
            } elseif (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = self::createUploadedFileFromSpec($value);
            } elseif (is_array($value)) {
                $normalized[$key] = self::normalizeFiles($value);
            } else {
                throw new InvalidArgumentException(
                    'Invalid value in files specification'
                );
            }
        }

        return $normalized;
    }

    /**
     * Create and return an UploadedFile instance from a $_FILES specification.
     *
     * If the specification represents an array of values, this method will
     * delegate to normalizeNestedFileSpec() and return that return value.
     *
     * @param array $value $_FILES struct
     *
     * @return UploadedFileInterface|UploadedFileInterface[]
     */
    private static function createUploadedFileFromSpec(array $value) : array|UploadedFileInterface
    {
        if (is_array($value['tmp_name'])) {
            return self::normalizeNestedFileSpec($value);
        }

        return new UploadedFile(
            $value['tmp_name'],
            (int) $value['size'],
            (int) $value['error'],
            $value['name'],
            $value['type']
        );
    }

    /**
     * Normalize an array of file specifications.
     *
     * Loops through all nested files and returns a normalized array of
     * UploadedFileInterface instances.
     *
     * @return UploadedFileInterface[]
     */
    private static function normalizeNestedFileSpec(array $files = []) : array
    {
        $normalizedFiles = [];
        foreach (array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size'     => $files['size'][$key],
                'error'    => $files['error'][$key],
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
            ];
            $normalizedFiles[$key] = self::createUploadedFileFromSpec($spec);
        }

        return $normalizedFiles;
    }

    /**
     * Return a ServerRequest populated with super globals:
     * $_GET
     * $_POST
     * $_COOKIE
     * $_FILES
     * $_SERVER
     */
    public static function fromGlobals() : ServerRequestInterface
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
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

        $uri      = Uri::fromGlobals();
        $body     = Stream::fromFile('php://input', 'r+');
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';
        $serverRequest = new ServerRequest($method, $uri, $headers, $body, $protocol, $_SERVER);
        return $serverRequest
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withUploadedFiles(self::normalizeFiles($_FILES));
    }

    public function getServerParams() : array
    {
        return $this->serverParams;
    }

    public function getUploadedFiles() : array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles) : ServerRequestInterface
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    public function getCookieParams() : array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies) : ServerRequestInterface
    {
        $new = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    public function getQueryParams() : array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query) : ServerRequestInterface
    {
        $new = clone $this;
        $new->queryParams = $query;

        return $new;
    }

    /**
     * {@inheritdoc}
     *
     * @return array|object|null
     */
    public function getParsedBody() : object|array|null
    {
        return $this->parsedBody;
    }

    /**
     * @param array|object|null $data
     *
     * @return ServerRequestInterface
     */
    public function withParsedBody($data) : ServerRequestInterface
    {
        $new = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    public function getAttributes() : array
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function getAttribute($name, $default = null) : mixed
    {
        if (false === array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    public function withAttribute($name, $value) : ServerRequestInterface
    {
        $obj = clone $this;
        $obj->attributes[$name] = $value;

        return $obj;
    }

    public function withoutAttribute($name) : ServerRequestInterface
    {
        if (false === array_key_exists($name, $this->attributes)) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }
}
