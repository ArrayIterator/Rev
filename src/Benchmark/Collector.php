<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Benchmark;

use Countable;
use JsonSerializable;
use ReflectionClass;

final class Collector implements Countable, JsonSerializable
{

    private ReflectionClass $reflection;

    private float $start_time;

    private int $start_memory;

    /**
     * @var array<string, Group>
     */
    private array $groups = [];

    public function __construct()
    {
        $this->start_time = microtime(true);
        $this->start_memory = memory_get_usage(true);
        $this->reflection = new ReflectionClass(Group::class);
    }

    /**
     * @return float
     */
    public function getStartTime(): float
    {
        return $this->start_time;
    }

    /**
     * @return int
     */
    public function getStartMemory(): int
    {
        return $this->start_memory;
    }

    public function hasGroup(string $name): bool
    {
        return isset($this->groups[$name]);
    }

    public function addGroup(Group $group): void
    {
        if (!isset($this->groups[$group->getName()])
            && count($group) > 0
        ) {
            $this->groups[$group->getName()] = $group;
        }
    }

    public function has(string $id): bool
    {
        return isset($this->groups[$id]);
    }

    /**
     * @param string $id
     * @return ?Group
     */
    public function get(string $id): ?Group
    {
        return $this->groups[$id]??null;
    }

    public function group(string $id) : Group
    {
        if (!isset($this->groups[$id])) {
            /**
             * @noinspection PhpUnhandledExceptionInspection
             */
            return (function ($obj) use ($id) {
                /**
                 * @var Group $this
                 */
                $this->{'__construct'}($obj, $id);
                return $this;
            })->call($this->reflection->newInstanceWithoutConstructor(), $this);
        }

        return $this->groups[$id];
    }

    /**
     * @return array<string, Group>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param string $name
     * @param array|null $context
     * @param string $group
     * @return Record
     */
    public function start(string $name, ?array $context = null, string $group = 'default'): Record
    {
        return $this->group($group)->start($name, $context);
    }

    public function stop(string|Record|null $name = null, ?array $context = null, string $group = 'default'): ?Record
    {
        return $this->get($group)?->stop($name, $context);
    }

    public function count(): int
    {
        return count($this->groups);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        $start_time = $this->getStartTime();
        $start_memory = $this->getStartMemory();
        $end_time = microtime(true);
        $end_memory = memory_get_usage(true);
        $used = $end_memory - $start_memory;
        return [
            'start_time' => $start_time,
            'start_memory' => $start_memory,
            'end_time' => $end_time,
            'end_memory' => $end_memory,
            'elapsed_time' => $end_time - $start_time,
            'used_memory' => max($used, 0),
            'groups' => $this->groups
        ];
    }

    public function clear(): void
    {
        $this->groups = [];
    }

    public function __destruct()
    {
        $this->clear();
    }
}
