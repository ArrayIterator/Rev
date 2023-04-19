<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Request;

use ArrayIterator\Rev\Source\Events\EventsManagerInterface;
use ArrayIterator\Rev\Source\Http\Code;
use ArrayIterator\Rev\Source\Http\Request;
use ArrayIterator\Rev\Source\Http\Request\Exceptions\BadRequestException;
use ArrayIterator\Rev\Source\Http\Request\Exceptions\BadResponseException;
use ArrayIterator\Rev\Source\Http\Request\Exceptions\ConnectException;
use ArrayIterator\Rev\Source\Http\Request\Exceptions\TimeoutException;
use ArrayIterator\Rev\Source\Http\Request\Exceptions\UnknownResponseErrorException;
use ArrayIterator\Rev\Source\Traits\ResponseFactoryTrait;
use CurlHandle;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class Curl implements ClientRequestInterfaces
{
    use ResponseFactoryTrait;

    const VERSION  = '1.0.0';

    const DEFAULT_TIMEOUT = 60;

    const DEFAULT_CONNECT_TIMEOUT = 5;

    const DEFAULT_USER_AGENT_PREFIX = 'Reactor/';

    private RequestInterface $request;

    private array $options;

    private array $params = [];

    private ?CurlHandle $resource = null;

    private ?ResponseInterface $response = null;

    /**
     * @var BadResponseException|ResponseResult|null
     */
    private null|BadResponseException|ResponseResult $result = null;

    private ?ContainerInterface $container;

    public function __construct(
        RequestInterface $request,
        array $options = [],
        protected ?EventsManagerInterface $eventsManager = null,
        ?ContainerInterface $container = null
    ) {
        $this->request = $request;
        $this->options = $options;
        $this->container = $container;
    }

    /**
     * @return ?ContainerInterface
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @return EventsManagerInterface|null
     */
    public function getEventsManager(): ?EventsManagerInterface
    {
        return $this->eventsManager;
    }

    /**
     * @param EventsManagerInterface $eventsManager
     */
    public function setEventsManager(EventsManagerInterface $eventsManager): void
    {
        $this->eventsManager = $eventsManager;
    }

    /**
     * @param string|UriInterface $uri
     * @param string $method
     * @param array $options
     * @param EventsManagerInterface|null $eventsManager
     * @param ContainerInterface|null $container
     * @return static
     */
    public static function createRequest(
        string|UriInterface $uri,
        string $method = 'GET',
        array $options = [],
        ?EventsManagerInterface $eventsManager = null,
        ?ContainerInterface $container = null
    ) : static {
        $request = new Request(
            $method,
            $uri,
            []
        );
        return new static($request, $options, $eventsManager, $container);
    }

    /**
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    public function getResource(): ?CurlHandle
    {
        if ($this->resource) {
            return $this->resource;
        }

        $this->resource = curl_init((string) $this->getRequest()->getUri());
        $this->params = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => ($this->options['verify']??null) !== false,
            CURLOPT_SSL_VERIFYPEER => ($this->options['verify']??null) !== false,
            CURLOPT_SSL_VERIFYSTATUS => ($this->options['verify']??null) !== false,
            CURLOPT_CUSTOMREQUEST => $this->request->getMethod(),
            CURLOPT_IPRESOLVE     => ($this->options['ip4']??$this->options['ipv4']??null) === true
                ? CURL_IPRESOLVE_V4
                : (
                ($this->options['ip6']??$this->options['ipv6']??null) === true
                    ? CURL_IPRESOLVE_V6
                    : CURL_IPRESOLVE_WHATEVER
                ),
            CURLOPT_FOLLOWLOCATION => !(($this->options['follow_location'] ?? null) === false),
            CURLOPT_MAXREDIRS => is_int($this->options['max_redirect'] ?? null)
                && $this->options['max_redirect'] > 0
                ? $this->options['max_redirect']
                : 10,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => is_int($this->options['timeout'] ?? null)
                ? ($this->options['timeout'] > -1 ? $this->options['timeout'] : 0)
                : self::DEFAULT_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => is_int($this->options['connect_timeout'] ?? null)
                ? ($this->options['connect_timeout'] > 0
                    ? $this->options['connect_timeout']
                    : self::DEFAULT_CONNECT_TIMEOUT
                )
                : self::DEFAULT_CONNECT_TIMEOUT,
            CURLOPT_VERBOSE => ($this->options['verbose']??null) === true,
        ];

        if ($this->request->getMethod() === 'POST') {
            $this->params[CURLOPT_POST] = true;
            unset($this->params[CURLOPT_CUSTOMREQUEST]);
        }

        if (is_string($this->options['referer']??null)
            || ($this->options['referer']??null) instanceof UriInterface
        ) {
            $this->params[CURLOPT_REFERER] = (string) $this->options['referer'];
        } elseif (is_bool($this->options['auto_referer']??null)) {
            $this->params[CURLOPT_AUTOREFERER] = $this->options['auto_referer'];
        }
        $headers = $this->options['headers']??[];
        $headers =!is_array($headers) ? [] : $headers;
        $headers = array_filter($headers, 'is_string', ARRAY_FILTER_USE_KEY);
        $headers = array_change_key_case($headers, CASE_LOWER);
        $newHeaders = [];
        foreach ($headers as $key => $header) {
            $key = ucfirst(ucwords(strtolower($key), '-'));
            $newHeaders[$key] = $header;
            $this->request = $this->request->withHeader($key, $header);
        }
        if (isset($this->params['curl'][CURLOPT_USERAGENT])
            && is_string($this->params['curl'][CURLOPT_USERAGENT])
            && trim($this->params['curl'][CURLOPT_USERAGENT]) !== ''
        ) {
            $this->params['curl'][CURLOPT_USERAGENT] = trim($this->params['curl'][CURLOPT_USERAGENT]);
            $newHeaders['User-Agent'] ??= $this->params['curl'][CURLOPT_USERAGENT];
        }

        unset($this->params['curl'][CURLOPT_USERAGENT]);
        $newHeaders['User-Agent'] ??= self::DEFAULT_USER_AGENT_PREFIX . static::VERSION;

        if ($this->request->getMethod() !== 'GET') {
            $this->request->getBody()->rewind();
            if (isset($this->options['json'])) {
                if (is_string($this->options['json'])) {
                    @json_decode($this->options['json'], true);
                    if (json_last_error()) {
                        $this->options['json'] = json_encode($this->options['json']);
                    }
                } else {
                    $this->options['json'] = json_encode($this->options['json']);
                }
                $this->request->getBody()->write($this->options['json']);
            } elseif (isset($this->options['formData'])) {
                $newHeaders['Content-Type'] = 'multipart/form-data';
                if (is_array($this->options['formData']) || is_string($this->options['formData'])) {
                    $this->params[CURLOPT_POSTFIELDS] = $this->options['formData'];
                } elseif ($this->request->getBody()->getSize() > 0) {
                    $this->params[CURLOPT_POSTFIELDS] = (string)$this->request->getBody();
                } else {
                    $this->params[CURLOPT_POSTFIELDS] = (string)$this->options['formData'];
                }
            } elseif (is_array($this->options['formParams']??null)) {
                $newHeaders['Content-Type']       = 'application/x-www-form-urlencoded';
                $this->params[CURLOPT_POSTFIELDS] = http_build_query($this->options['formParams']??[]);
            }
            if (isset($newHeaders['Content-Type'])) {
                $this->request = $this->request->withHeader('Content-Type', $newHeaders['Content-Type']);
            }
        }

        if (isset($this->options['curl']) && is_array($this->options['curl'])) {
            $options = $this->options['curl'];
            unset(
                $options[CURLOPT_HEADER],
                $options[CURLOPT_WRITEFUNCTION],
                $options[CURLOPT_RETURNTRANSFER],
                $options[CURLOPT_HEADERFUNCTION],
            );
            foreach ($options as $key => $option) {
                $this->params[$key] = $option;
            }
        }

        $this->getResponse()->getBody()->rewind();
        $this->params[CURLOPT_WRITEFUNCTION] = function ($handle, $str) {
            $this->getEventsManager()?->dispatch(
                'curl.onWriteFunction',
                $this,
                $str
            );
            $this->getResponse()->getBody()->write($str);
            return strlen($str);
        };
        // always return transfer
        $this->params[CURLOPT_RETURNTRANSFER] = true;
        $this->params[CURLOPT_HEADERFUNCTION] = function ($ch, string $header_line) {
            $this->getEventsManager()?->dispatch(
                'curl.onHeaderFunction',
                $this,
                $header_line
            );
            return $this->processHeader($header_line);
        };

        curl_setopt_array($this->resource, $this->params);

        return $this->resource;
    }

    private function getResponse() : ResponseInterface
    {
        if (!$this->response) {
            $this->response = $this->getResponseFactory()->createResponse();
        }

        return $this->response;
    }

    private function processHeader(string $headerLine) : int
    {
        $this->response = $this->getResponse();
        $length = strlen($headerLine);
        $header = trim($headerLine);
        if (! str_contains($header, ':')
        ) {
            if (preg_match('~^\s*HTTP/([^\s]+)\s+([0-9]+)\s+(.+)?$~', $header, $match)) {
                $match[2]       = (int)$match[2];
                $this->response = $this->response->withStatus(
                    $match[2],
                    trim($match[3] ?? '')
                )->withProtocolVersion(trim($match[1]));
            }
            return $length;
        }

        $header = explode(':', $header, 2);
        $key    = trim(array_shift($header));
        $header = trim(implode(':', $header));
        $this->response = $this->response->withAddedHeader($key, $header);
        return $length;
    }

    /**
     * @return BadResponseException|ResponseResult|null
     */
    public function getResult(): BadResponseException|ResponseResult|null
    {
        return $this->result;
    }

    /**
     * @param array $curlInfo
     * @param int $errorCode
     * @param string $curlError
     * @param null $processed
     *
     * @return ResponseResult
     * @access internal
     * @throws BadResponseException|ConnectException
     */
    public function formatInternal(
        array $curlInfo,
        int $errorCode,
        string $curlError,
        &$processed = null
    ): ResponseResult {
        $processed = false;
        if ($this->result) {
            return $this->result;
        }

        if ($this->resource) {
            @curl_close($this->resource);
        }
        $this->resource = null;
        $processed = true;
        switch ($errorCode) {
            case CURLE_OK:
                $this->response = $this->getResponse();
                $this->response->getBody()->rewind();
                $reason = $this->response->getReasonPhrase();
                if ($curlInfo['http_code'] >= 100 && $curlInfo['http_code'] < 400) {
                    $this->response = $this->response->withStatus(
                        $curlInfo['http_code'],
                        $reason
                    );
                    $this->result   = new ResponseResult(
                        $this->request,
                        $this->response,
                        $curlInfo
                    );
                    return $this->result;
                }

                if ($curlInfo['http_code'] >= 400 && $curlInfo['http_code'] < 600) {
                    if (!$curlError) {
                        $curlError = (string)$this->response->getBody();
                    }
                    $this->response = $this->response->withStatus($curlInfo['http_code']);
                    throw new BadResponseException(
                        $this->request,
                        $this->response,
                        $curlInfo,
                        $curlError
                    );
                }
                $this->response = $this->response->withStatus(
                    Code::statusMessage((int) $curlInfo['http_code'])
                        ? $curlInfo['http_code']
                        : 500
                );
                throw new UnknownResponseErrorException(
                    $this->request,
                    $this->response,
                    $curlInfo,
                    $curlError
                );
            case CURLE_OPERATION_TIMEOUTED:
            case CURLE_OPERATION_TIMEDOUT:
                throw new TimeoutException(
                    $this->request,
                    $curlInfo,
                    $curlError
                );
            case CURLE_SSL_CONNECT_ERROR:
            case CURLE_COULDNT_CONNECT:
            case CURLE_COULDNT_RESOLVE_HOST:
            case CURLE_COULDNT_RESOLVE_PROXY:
                throw new ConnectException(
                    $this->request,
                    $curlInfo,
                    $curlError
                );
        }
        if (!$this->response || $this->response->getStatusCode() === 200) {
            throw new BadRequestException(
                $this->request,
                $curlInfo,
                $curlError
            );
        }
        if (!$curlError) {
            $curlError = (string)$this->response->getBody();
        }
        $this->response = $this->response->withStatus(
            Code::statusMessage((int) $curlInfo['http_code'])
                ? $curlInfo['http_code']
                : 500
        );
        throw new BadResponseException(
            $this->request,
            $this->response,
            $curlInfo,
            $curlError
        );
    }

    /**
     * @return ResponseResult
     * @throws BadResponseException|ConnectException
     */
    public function execute() : ResponseResult
    {
        if ($this->result !== null) {
            return $this->result;
        }

        $ch       = $this->getResource();
        curl_exec($ch);
        $info = curl_getinfo($ch);
        $errNo = curl_errno($ch);
        $errMessage = curl_error($ch);
        curl_close($ch);
        $this->resource = null;
        // write
        $this->result = $this->formatInternal(
            $info,
            $errNo,
            $errMessage
        );
        return $this->result;
    }

    public function __destruct()
    {
        if ($this->resource) {
            curl_close($this->resource);
        }
        $this->resource = null;
        $this->response = null;
        $this->result = null;
    }
}
