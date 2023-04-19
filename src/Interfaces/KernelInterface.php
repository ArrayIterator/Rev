<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Interfaces;

interface KernelInterface
{
    public static function isBooted() : bool;

    public static function isShutdown() : bool;

    public static function prepare();

    public static function boot();

    public static function shutdown();
}
