<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Benchmark;

use JsonSerializable;

final class Record implements JsonSerializable
{
    public readonly string $name;

    public readonly Group $group;

    protected float $start_time;

    protected int $start_memory;

    protected ?float $end_time = null;

    protected ?int $end_memory = null;

    private ?float $elapsedTime= null;

    private ?int $usedMemory = null;

    protected ?array $context;

    private bool $stopped = false;

    public function __construct(
        Group $group,
        string $name,
        ?array $context = null
    ) {
        $this->name = $name;
        $this->group = $group;
        $this->start_time = microtime(true);
        $this->start_memory = memory_get_usage(true);
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isStopped(): bool
    {
        return $this->stopped;
    }

    public function stop(?array $context = null): self
    {
        if ($this->stopped) {
            return $this;
        }

        $this->stopped = true;
        if ($context !== null) {
            $this->context = array_merge($this->context??[], $context);
        }

        $this->end_time = microtime(true);
        $this->end_memory = memory_get_usage(true);
        $this->elapsedTime = $this->end_time - $this->start_time;
        $this->usedMemory  = $this->end_memory - $this->start_memory;
        if ($this->usedMemory < 0) {
            $this->usedMemory = 0;
        }

        $this->group->stop($this);
        return $this;
    }

    /**
     * @return Group
     */
    public function getGroup(): Group
    {
        return $this->group;
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

    /**
     * @return float|null
     */
    public function getEndTime(): ?float
    {
        return $this->end_time;
    }

    /**
     * @return int|null
     */
    public function getEndMemory(): ?int
    {
        return $this->end_memory;
    }

    /**
     * @return float|null
     */
    public function getElapsedTime(): ?float
    {
        return $this->elapsedTime;
    }

    /**
     * @return int|null
     */
    public function getUsedMemory(): ?int
    {
        return $this->usedMemory;
    }

    /**
     * @return array|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * @return array{
     *     group:string,
     *     name: string,
     *     start_time: float,
     *     start_memory: int,
     *     end_time: float,
     *     end_memory: int,
     *     elapsed_time: float,
     *     used_memory: int,
     *     context: ?array,
     * }
     */
    public function jsonSerialize(): array
    {
        $json = $this->toArray();
        if ($json['end_time'] === null) {
            $start_time = $json['start_time'];
            $start_memory = $json['start_memory'];
            $end_time = microtime(true);
            $end_memory = memory_get_usage(true);
            $elapsed = $end_time - $start_time;
            $used = $end_memory - $start_memory;
            $json['end_time'] = $end_time;
            $json['end_memory'] = $end_memory;
            $json['used_memory'] = max($used, 0);
            $json['elapsed_time'] = $elapsed;
        }

        return $json;
    }

    /**
     * @return array{
     *     group:string,
     *     name: string,
     *     start_time: float,
     *     start_memory: int,
     *     end_time: ?float,
     *     end_memory: ?int,
     *     elapsed_time: ?float,
     *     used_memory: ?int,
     *     context: ?array,
     * }
     */
    public function toArray(): array
    {
        return [
            'group' => $this->group->getName(),
            'name' => $this->getName(),
            'start_time' => $this->getStartTime(),
            'start_memory' => $this->getStartMemory(),
            'end_time' => $this->getEndTime(),
            'end_memory' => $this->getEndMemory(),
            'elapsed_time' => $this->getElapsedTime(),
            'used_memory' => $this->getUsedMemory(),
            'context' => $this->getContext()
        ];
    }
}
