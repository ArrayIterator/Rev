<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Query;

use Countable;

final class Expression implements Countable
{
    public const TYPE_AND = 'AND';

    public const TYPE_OR = 'OR';

    private string $type;

    /**
     * Each expression part of the composite expression.
     *
     * @var Expression[]|string[]
     */
    private array $parts;

    protected function __construct(string $type, array $parts = [])
    {
        $this->type = $type;
        $this->parts = $parts;
    }

    /**
     * @param self|string $part
     * @param self|string ...$parts
     *
     * @return Expression
     */
    public static function and(Expression|string $part, ...$parts): self
    {
        return new self(type: self::TYPE_AND, parts: array_merge([$part], $parts));
    }

    public static function or(Expression|string $part, Expression|string ...$parts): self
    {
        return new self(type: self::TYPE_OR, parts: array_merge([$part], $parts));
    }

    public function with(string|Expression $part, string|Expression ...$parts): self
    {
        $that = clone $this;
        $that->parts = array_merge($that->parts, [$part], $parts);

        return $that;
    }

    public function count() : int
    {
        return count($this->parts);
    }

    public function __toString()
    {
        if ($this->count() === 1) {
            return (string) $this->parts[0];
        }

        return '(' . implode(') ' . $this->type . ' (', $this->parts) . ')';
    }

    public function getType(): string
    {
        return $this->type;
    }
}
