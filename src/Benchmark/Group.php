<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Benchmark;

use Countable;
use JsonSerializable;
use RuntimeException;

final class Group implements Countable, JsonSerializable
{
    private ?float $start_time = null;

    private ?int $start_memory = null;

    /**
     * @var array<string, array<string,Record>>
     */
    private array $benchmarks = [];

    /**
     * @var array<string,string>
     */
    private array $queue = [];

    private ?Collector $collector = null;

    private string $name;

    private bool $inserted = false;

    final private function __construct(
        Collector $timer,
        string $name
    ) {
        $this->collector = $timer;
        $this->name = $name;
    }

    /**
     * @return Collector
     */
    public function getCollector(): Collector
    {
        return $this->collector;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function start(
        string $name,
        ?array $context = null
    ): Record {
        if (!isset($this->benchmarks[$name])) {
            $this->benchmarks[$name] = [];
            $this->start_time ??= microtime(true);
            $this->start_memory ??= memory_get_usage(true);
        }

        $record = new Record(group: $this, name: $name, context: $context);
        $id = spl_object_hash($record);
        $this->benchmarks[$name][$id] = $record;
        $this->queue[$id] = $name;
        $this->assertInstance();
        return $record;
    }

    public function has(string $name): bool
    {
        return isset($this->benchmarks[$name]);
    }

    /**
     * @param string $name
     * @return ?Record
     */
    public function get(string $name): ?Record
    {
        $bench = $this->getBenchmark($name)??[];
        return end($bench)?:null;
    }

    public function getBenchmark(string $name) : ?array
    {
        return $this->benchmarks[$name]??null;
    }

    /**
     * @param ?Record|string $record
     * @param ?array $context
     * @return ?Record
     */
    public function stop(Record|string|null $record = null, ?array $context = null): ?Record
    {
        $id = null;
        if ($record === null) {
            end($this->queue);
            $id   = key($this->queue);
            if ($id === null) {
                return null;
            }
            $name = $this->queue[$id]??null;
            if ($name === null) {
                return null;
            }
            unset($this->queue[$id]);
            $record = $this->benchmarks[$name][$id];
        } elseif (is_string($record)) {
            $name = null;
            if (!empty($this->queue)) {
                foreach (array_reverse($this->queue) as $_id => $named) {
                    if ($named === $record) {
                        $id = $_id;
                        $name = $named;
                        break;
                    }
                }
            }
            if ($name === null) {
                if (empty($this->benchmarks[$record])) {
                    return null;
                }

                $first = null;
                $id = null;
                foreach (array_reverse($this->benchmarks[$record]) as $_id => $record) {
                    if (!$first) {
                        $id = $_id;
                        $first = $record;
                    }
                    if (!$record->isStopped()) {
                        $id = $_id;
                        $first = $record;
                        break;
                    }
                }
                $record = $first;
            } else {
                $record = $this->benchmarks[$name][$id]??null;
            }
        } else {
            $name = $record->getName();
            $id = spl_object_hash($record);
            $this->benchmarks[$name][$id] = $record;
        }

        if ($id !== null) {
            unset($this->queue[$id]);
        }

        return $record?->stop($context);
    }

    /**
     * @return array
     */
    public function getBenchmarks(): array
    {
        return $this->benchmarks;
    }

    private function assertInstance(): void
    {
        if (!$this->collector || $this->inserted) {
            return;
        }
        $this->inserted = true;
        $this->collector->addGroup($this);
    }

    public function clear(): void
    {
        $this->benchmarks = [];
    }

    public function __destruct()
    {
        $this->clear();
    }

    /**
     * @return array
     */
    public function getQueue(): array
    {
        return $this->queue;
    }

    /**
     * @return float|null
     */
    public function getStartTime(): ?float
    {
        return $this->start_time;
    }

    /**
     * @return int|null
     */
    public function getStartMemory(): ?int
    {
        return $this->start_memory;
    }

    /**
     * @return bool
     */
    public function isInserted(): bool
    {
        return $this->inserted;
    }

    public function __clone(): void
    {
        throw new RuntimeException(
            sprintf(
                'Class %s can not being clone.',
                __CLASS__
            ),
            E_USER_ERROR
        );
    }

    public function count(): int
    {
        return count($this->benchmarks);
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
            'records' => array_values($this->benchmarks)
        ];
    }
}
