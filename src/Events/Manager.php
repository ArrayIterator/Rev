<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Events;

use Countable;

final class Manager implements Countable
{
    private static ?EventsManager $eventsManager = null;

    final private function __construct()
    {
    }

    public static function generateCallableId($callback): array|null
    {
        return EventsManager::generateCallableId($callback);
    }

    public static function getEventsManager(): EventsManager
    {
        self::$eventsManager ??= new EventsManager();
        return self::$eventsManager;
    }

    public static function attach(string $eventName, $callable, int $priority = 10): string
    {
        return self::getEventsManager()->attach($eventName, $callable, $priority);
    }

    public static function detach(
        string $eventName,
        $callback = null,
        ?int $priority = null
    ): int {
        return self::$eventsManager?->detach($eventName, $callback, $priority)??0;
    }

    public static function detachAll(string ...$eventNames): int
    {
        return self::$eventsManager?->detachAll(...$eventNames)??0;
    }

    public static function dispatched(
        string $eventName,
        $callback = null,
        ?int $priority = null
    ): int {
        return self::$eventsManager?->dispatched($eventName, $callback, $priority)??0;
    }
    public static function inside(string $eventName, $callback = null): bool
    {
        return self::$eventsManager?->insideOf($eventName, $callback)??false;
    }

    public static function getCurrentEvent(): callable|array|string|null
    {
        return self::$eventsManager?->getCurrentEvent();
    }

    public static function getCurrentParams(): ?array
    {
        return self::$eventsManager?->getCurrentParams();
    }

    public static function dispatch(
        string $eventName,
        $param = null,
        ...$arguments
    ) {
        if (!self::$eventsManager) {
            return $param;
        }

        $num = func_num_args();
        if ($num === 1) {
            return self::$eventsManager->dispatch($eventName);
        }
        if ($num === 2) {
            return self::$eventsManager->dispatch($eventName, $param);
        }
        return self::$eventsManager->dispatch($eventName, $param, ...$arguments);
    }

    public function count(): int
    {
        return self::$eventsManager?->count()??0;
    }
}
