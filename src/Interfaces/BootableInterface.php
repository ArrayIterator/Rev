<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Interfaces;

interface BootableInterface
{
    public function isBooted() : bool;

    public function isShutdown() : bool;

    public function prepare();

    public function boot();

    public function shutdown();
}
