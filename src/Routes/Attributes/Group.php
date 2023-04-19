<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Routes\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Group
{
    public function __construct(public string $pattern)
    {
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }
}
