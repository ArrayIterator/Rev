<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Events;

use Countable;

interface EventsManagerInterface extends Countable
{
    public function attach(string $name, $callback, int $priority = 10) : string;

    public function detach(
        string $eventName,
        $callback = null,
        ?int $priority = null
    ): int;

    public function detachAll(string ...$eventNames): int;

    public function dispatched(
        string $eventName,
        $callback = null,
        ?int $priority = null
    ): int;

    public function insideOf(string $eventName, $callback = null): bool;

    public function getCurrentEvent(): callable|array|string|null;

    /**
     * @return array|null
     */
    public function getCurrentParams(): ?array;

    /**
     * @param string $eventName
     * @param $param
     * @param ...$arguments
     *
     * @return mixed
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function dispatch(
        string $eventName,
        $param = null,
        ...$arguments
    );

    public function count(): int;
}
