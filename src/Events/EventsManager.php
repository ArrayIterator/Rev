<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Events;

use ArrayIterator\Rev\Source\Events\Exceptions\InvalidCallbackException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

final class EventsManager implements EventsManagerInterface
{
    protected array $events = [];

    protected array $currents = [];

    /**
     * @var array{string: array{int:array{string:int}}}
     */
    protected array $records = [];

    /**
     * @var ?callable|array|string
     */
    protected $currentEvent = null;

    protected ?array $currentParams = null;

    public static function generateCallableId($callback): array|null
    {
        if (!$callback) {
            return null;
        }

        if (is_string($callback)) {
            try {
                $callback = (new ReflectionFunction($callback))->getName();
                return [
                    $callback,
                    $callback,
                ];
            } catch (ReflectionException) {
                return null;
            }
        }

        if (is_object($callback)) {
            return [
                spl_object_hash($callback),
                $callback
            ];
        }

        if (!is_array($callback)) {
            return null;
        }

        $obj = array_shift($callback);
        $method = array_shift($callback);
        if ($method !== false && !is_string($method)) {
            return null;
        }

        if ($method) {
            try {
                $ref = (new ReflectionMethod($obj, $method));
                if (is_string($obj) && (
                    ! $ref->isPublic() || ! $ref->isStatic()
                )) {
                    return null;
                }
                $method = $ref->getName();
            } catch (ReflectionException) {
                return null;
            }
        }

        try {
            $id = is_object($obj) ? spl_object_hash($obj) : (new ReflectionClass($obj))->getName();
        } catch (ReflectionException) {
            $id = $obj;
        }

        if (!empty($method)) {
            $id .= "::$method";
            return [
                $id,
                [
                    is_object($obj) ? $obj : $id,
                    $method
                ]
            ];
        }

        return [
            $id,
            is_string($obj) ? $id : $obj
        ];
    }

    public function attach(string $name, $callback, int $priority = 10) : string
    {
        $callable = $this->generateCallableId($callback);
        if ($callable === null) {
            throw new InvalidCallbackException(
                'Argument 2 must is not valid callback.'
            );
        }

        $id = array_shift($callable);
        $callback = array_shift($callable);
        $this->events[$name][$priority][$id][] = $callback;
        return $id;
    }

    public function detach(
        string $eventName,
        $callback = null,
        ?int $priority = null
    ): int {
        $deleted = 0;
        if (!isset($this->events[$eventName])) {
            return 0;
        }

        $id = null;
        if ($callback) {
            $callable = $this->generateCallableId($callback);
            if ($callable === null) {
                return 0;
            }
            $id = array_shift($callable);
        }

        foreach ($this->events[$eventName] as $priorityId => $callback) {
            if ($priority !== null && $priorityId !== $priority) {
                continue;
            }
            if ($id === null) {
                foreach ($callback as $item) {
                    $deleted += count($item);
                }
                continue;
            }
            if (!isset($callback[$id])) {
                continue;
            }
            $deleted += count($callback[$id]);
        }

        return $deleted;
    }

    public function detachAll(string ...$eventNames): int
    {
        $total = 0;
        if (count($eventNames) === 0) {
            array_map(static function ($arr) use (&$total) {
                array_map(static function ($arr) use (&$total) {
                    $total += count($arr);
                }, $arr);
            }, $this->currents);
            $this->events = [];
            return $total;
        }
        foreach ($eventNames as $name) {
            $total += $this->detach($name);
        }
        return $total;
    }

    public function dispatched(
        string $eventName,
        $callback = null,
        ?int $priority = null
    ): int {
        $dispatched = 0;
        if (!isset($this->records[$eventName])) {
            return $dispatched;
        }

        $id = null;
        if ($callback) {
            $callable = $this->generateCallableId($callback);
            if ($callable === null) {
                return 0;
            }
            $id = array_shift($callable);
        }
        if ($priority !== null) {
            if (!isset($this->records[$eventName][$priority])) {
                return $dispatched;
            }
            if ($id !== null) {
                return $this->records[$eventName][$priority][$id] ?? $dispatched;
            }
            foreach ($this->records[$eventName][$priority] as $record) {
                $dispatched += $record;
            }
            return $dispatched;
        }
        foreach ($this->records[$eventName] as $records) {
            if ($id !== null) {
                $dispatched += $records[$id]??0;
                continue;
            }
            foreach ($records as $count) {
                $dispatched += $count;
            }
        }

        return $dispatched;
    }

    public function insideOf(string $eventName, $callback = null): bool
    {
        if (!isset($this->currents[$eventName])) {
            return false;
        }

        if (!$callback) {
            return true;
        }

        $callable = $this->generateCallableId($callback);
        if ($callable === null) {
            return false;
        }
        $id = array_shift($callable);
        return isset($this->currents[$eventName][$id]);
    }

    public function getCurrentEvent(): callable|array|string|null
    {
        return $this->currentEvent;
    }

    /**
     * @return array|null
     */
    public function getCurrentParams(): ?array
    {
        return $this->currentParams;
    }

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
    ) {
        if (!isset($this->events[$eventName])) {
            return $param;
        }
        if (!isset($this->currents[$eventName])) {
            $this->currents[$eventName] = [];
        }

        ksort($this->events[$eventName]);
        // make temporary to prevent remove
        $originalParams = $param;
        $events = $this->events[$eventName];
        foreach ($events as $priority => $callableList) {
            unset($events[$priority]);
            if (!isset($this->currents[$eventName][$priority])) {
                $this->currents[$eventName][$priority] = [];
            }
            foreach ($callableList as $id => $callback) {
                unset($callback[$id]);
                // prevent loops to call dispatch same
                if (isset($this->currents[$eventName][$priority][$id])) {
                    continue;
                }
                if (!isset($this->records[$eventName][$id])) {
                    $this->records[$eventName][$id] = 0;
                }
                $this->records[$eventName][$id]++;

                foreach ($callback as $inc => $callable) {
                    // prevent loops to call dispatch same
                    if (isset($this->currents[$eventName][$priority][$id][$inc])) {
                        continue;
                    }

                    $this->currents[$eventName][$priority][$id][$inc] = true;
                    $this->currentParams = func_get_args();
                    $this->currentEvent = $callback;
                    if (is_array($callable)
                        && !is_callable($callable)
                        && is_object($callable[0])
                        && is_string($callable[1]??null)
                    ) {
                        $method = $callable[1];
                        $param = (function (...$arguments) use ($method) {
                            return $this->$method($arguments);
                        })->call($callable[0], $param, ...$arguments);
                    } else {
                        $param = $callable($param, ...$arguments);
                    }
                    $this->currentParams = null;
                    $this->currentEvent = null;
                }

                unset($this->currents[$eventName][$priority][$id]);
            }

            unset($this->currents[$eventName][$priority]);
        }

        if (empty($this->currents[$eventName])) {
            unset($this->currents[$eventName]);
        }

        return $param;
    }

    public function count(): int
    {
        $total = 0;
        array_map(static function ($arr) use (&$total) {
            array_map(static function ($arr) use (&$total) {
                $total += count($arr);
            }, $arr);
        }, $this->records);
        return $total;
    }
}
