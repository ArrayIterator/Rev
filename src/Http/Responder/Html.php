<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Responder;

use ArrayIterator\Rev\Source\Events\EventsManagerInterface;
use ArrayIterator\Rev\Source\Http\Responder\Interfaces\HtmlResponderInterface;
use ArrayIterator\Rev\Source\Traits\ResponseFactoryTrait;
use ArrayIterator\Rev\Source\Traits\StreamFactoryTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Stringable;

class Html implements HtmlResponderInterface
{
    use StreamFactoryTrait,
        ResponseFactoryTrait;

    private ?ContainerInterface $container = null;

    private ?EventsManagerInterface $eventsManager = null;

    private string $contentType = 'text/html';

    private ?string $charset = null;

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
     * @return ?EventsManagerInterface
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

    public function setContentType(string $contentType): string
    {
        return $this->contentType;
    }

    /**
     * @param ?string $charset
     */
    public function setCharset(?string $charset): void
    {
        $this->charset = $charset ? (trim($charset)?:null) : null;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @return ?string
     */
    public function getCharset(): ?string
    {
        return $this->charset;
    }

    public function format(int $code, $data): string
    {
        $originalData = $data;
        if (is_scalar($data) || $data instanceof Stringable) {
            $data = (string) $data;
        } else {
            $data = print_r($data, true);
        }
        $newData = $this->getEventsManager()
            ?->dispatch(
                'html.response.format',
                $data,
                $originalData,
                $code
            );
        if (is_string($newData)
            || is_scalar($newData)
            || $newData instanceof Stringable
        ) {
            return (string) $newData;
        }
        return $data;
    }

    public function serve(int $code, $data, ResponseInterface $response = null): ResponseInterface
    {
        $eventsManager = $this->getEventsManager();
        $response ??= $this->getResponseFactory()?->createResponse($code);
        $newResponse = $eventsManager?->dispatch(
            'html.response.response',
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

        $body->write($this->format($code, $data));
        $contentType = $this->getContentType();
        $charset = $this->getCharset();
        if ($charset) {
            $contentType .= sprintf('; charset=%s', $charset);
        }

        return $response
            ->withStatus($code)
            ->withHeader('Content-Type', $contentType)
            ->withBody($body);
    }
}
