<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Responder;

use ArrayIterator\Rev\Source\Events\EventsManagerInterface;
use ArrayIterator\Rev\Source\Http\Code;
use ArrayIterator\Rev\Source\Http\Responder\Interfaces\JsonResponderInterface;
use ArrayIterator\Rev\Source\Traits\ResponseFactoryTrait;
use ArrayIterator\Rev\Source\Traits\StreamFactoryTrait;
use JsonSerializable;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Stringable;
use Throwable;

class Json implements JsonResponderInterface
{
    use StreamFactoryTrait,
        ResponseFactoryTrait;

    private ?ContainerInterface $container = null;

    private ?EventsManagerInterface $eventsManager = null;

    private string $contentType = 'application/json';

    public function __construct(
        ContainerInterface $container = null,
        EventsManagerInterface $eventsManager = null
    ) {
        if ($container) {
            $this->setContainer($container);
        }
        if ($eventsManager) {
            $this->setEventsManager($eventsManager);
        }
    }

    public function setContentType(string $contentType): string
    {
        $contentType = trim($contentType);
        preg_match('~^\s*((application/|)\s*)?((?:[^/]+\+)?(json)\s*(;.+))$~i', $contentType, $match);
        if (empty($match)) {
            return $this->contentType;
        }
        $match[1] = strtolower(trim($match[1]));
        if (trim($match[1]) === '') {
            $match[1] = 'application/';
        }
        $this->contentType = "$match[1]/$match[2]";
        return $contentType;
    }

    public function getContentType(): string
    {
        return $this->contentType;
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
     * @return ContainerInterface|null
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

    public function decode(string $data, bool $assoc = true)
    {
        $depth = $this->getEventsManager()?->dispatch(
            'json.response.decode.depth',
            512,
            $data,
            $assoc
        );
        $depth = is_int($depth) ? $depth : 512;
        return json_decode($data, $assoc, $depth);
    }

    public function encode($data): string
    {
        $flags = JSON_UNESCAPED_SLASHES;
        $flags = $this->getEventsManager()?->dispatch(
            'json.response.encode.flags',
            $flags,
            $data
        );
        $flags = is_int($flags) ? $flags : JSON_UNESCAPED_SLASHES;
        $depth = $this->getEventsManager()?->dispatch(
            'json.response.encode.depth',
            512,
            $data,
            $flags
        );
        if (($flags & JSON_THROW_ON_ERROR) !== JSON_THROW_ON_ERROR) {
            $flags |= JSON_THROW_ON_ERROR;
        }

        $depth = is_int($depth) ? $depth : 512;
        return json_encode($data, $flags, $depth);
    }

    public function format(int $code, $data): array
    {
        $originalData = $data;
        if ($code < 400) {
            $data = [
                'data' => $data
            ];
        } else // make sure message is string
        {
            $httpMessage = Code::statusMessage($code);
            if ($data === null) {
                $httpMessage = $httpMessage??sprintf('Error %d', $code);
            }
            $data = [
                'message' => $data
            ];
            if ($originalData instanceof Throwable) {
                $data['message'] = $originalData->getMessage();
                if ($this
                        ->getEventsManager()
                        ?->dispatch(
                            'json.response.debug',
                            true
                        ) === true
                ) {
                    $data['meta']['exception'] = [
                        'message' => $originalData->getMessage(),
                        'file' => $originalData->getFile(),
                        'line' => $originalData->getLine(),
                        'code' => $originalData->getCode(),
                        'trace' => $originalData->getTrace(),
                    ];
                }
            } else {
                if ($originalData instanceof JsonSerializable) {
                    $originalData = $originalData->jsonSerialize();
                } elseif ($originalData instanceof Stringable
                    || (is_object($originalData)
                        && method_exists($originalData, '__toString')
                    )
                ) {
                    $data['message'] = (string) $originalData;
                }
                if (is_array($originalData)) {
                    if (is_string($originalData['message']??null)) {
                        $message = $originalData['message'];
                        if (count($originalData) === 1) {
                            $data['message'] = $message;
                        } else {
                            $data = [
                                'message' => $message,
                                'meta' => $originalData
                            ];
                        }
                    } else {
                        $data = [
                            'message' => $httpMessage,
                            'meta' => $originalData
                        ];
                    }
                }
            }

            if (!is_string($data['message'])) {
                $data = [
                    'message' => $httpMessage,
                    'meta' => $data['message']
                ];
            }
        }

        $newData = $this->getEventsManager()
            ?->dispatch(
                'json.response.format',
                $data,
                $originalData,
                $code
            );
        if (is_array($newData)) {
            return $newData;
        }
        return $data;
    }

    public function serve(int $code, $data, ResponseInterface $response = null): ResponseInterface
    {
        $eventsManager = $this->getEventsManager();
        $response ??= $this->getResponseFactory()?->createResponse($code);
        $newResponse = $eventsManager?->dispatch(
            'json.response.response',
            $code,
            $data
        );
        if ($newResponse instanceof ResponseInterface) {
            $response = $newResponse;
        }

        $body = $response->getBody();
        if (!$body->isWritable() || $body->getSize() > 0) {
            $body = $this->getStreamFactory()->createStream();
        }
        $body->write($this->encode($this->format($code, $data)));
        return $response
            ->withStatus($code)
            ->withHeader('Content-Type', $this->getContentType())
            ->withBody($body);
    }
}
