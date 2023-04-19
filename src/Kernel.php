<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source;

use ArrayIterator\Rev\Source\Interfaces\KernelInterface;
use Psr\Http\Message\ServerRequestInterface;

class Kernel implements KernelInterface
{
    private static ?Application $application = null;

    final private function __construct()
    {
    }

    public static function application(): Application
    {
        if (!self::$application) {
            self::$application = new Application();
        }
        return self::$application;
    }

    public static function isBooted(): bool
    {
        return self::application()->isBooted();
    }

    public static function isShutdown(): bool
    {
        return self::application()->isShutdown();
    }

    public static function prepare(): Application
    {
        return self::application()->prepare();
    }

    public static function boot(?ServerRequestInterface $request = null): Application
    {
        return self::application()->boot($request);
    }

    public static function shutdown(): void
    {
        self::application()->shutdown();
    }
}
