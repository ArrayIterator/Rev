<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Benchmark;

/**
 * @uses Collector::hasGroup()
 * @method static bool hasGroup(string $name)
 * @uses Collector::group()
 * @method static Group group(string $id)
 * @uses Collector::getGroups()
 * @method static array<string, Group> getGroups()
 * @uses Collector::start()
 * @method static Record start(string $name, ?array $context = null, string $group = 'default')
 * @uses Collector::stop()
 * @method static Record|null stop(string $name, ?array $context = null, string $group = 'default')
 * @mixin Collector
 */
final class Timer
{
    private static ?Timer $timer = null;

    private Collector $collector;

    private function __construct()
    {
        self::$timer = $this;
        $this->collector ??= new Collector();
    }

    public static function getInstance() : Timer
    {
        self::$timer ??= new self();
        return self::$timer;
    }
    public static function getCollector() : Collector
    {
        return self::getInstance()->collector;
    }

    public static function __callStatic(string $name, array $arguments)
    {
        return call_user_func_array(
            [self::getCollector(), $name],
            $arguments
        );
    }

    public function __call(string $name, array $arguments)
    {
        return call_user_func_array(
            [self::getCollector(), $name],
            $arguments
        );
    }
}
