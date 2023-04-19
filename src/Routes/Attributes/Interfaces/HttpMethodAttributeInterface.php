<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Routes\Attributes\Interfaces;

interface HttpMethodAttributeInterface
{
    /**
     * @return array<string>
     */
    public function getMethods() : array;

    /**
     * @return string
     */
    public function getPattern(): string;

    /**
     * @return int
     */
    public function getPriority(): int;
    /**
     * @return string|null
     */
    public function getName(): ?string;
    /**
     * @return string|null
     */
    public function getHostName(): ?string;
}
