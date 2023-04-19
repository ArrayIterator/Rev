<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Traits;

use ArrayIterator\Rev\Source\Benchmark\Record;
use ArrayIterator\Rev\Source\Benchmark\Timer;

trait BenchmarkingTrait
{
    protected function benchmarkStart(string $name, ?array $context = null, string $group = 'default'): Record
    {
        return Timer::getCollector()->start($name, $context, $group);
    }

    protected function benchmarkStop(string $name, ?array $context = null, string $group = 'default'): Record
    {
        return Timer::getCollector()->start($name, $context, $group);
    }
}
