<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Routes\Attributes;

use ArrayIterator\Rev\Source\Routes\Attributes\Interfaces\HttpMethodAttributeInterface;
use ArrayIterator\Rev\Source\Routes\Route;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Map implements HttpMethodAttributeInterface
{
    /**
     * @var array<string>
     */
    public array $methods;

    public function __construct(
        array|string $methods,
        public string $pattern,
        public int $priority = Route::DEFAULT_PRIORITY,
        public ?string $name = null,
        public ?string $hostName = null
    ) {
        $this->methods = array_keys(Route::filterMethods($methods));
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getHostName(): ?string
    {
        return $this->hostName;
    }
}
