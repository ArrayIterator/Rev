<?php
namespace ArrayIterator\Rev\Public;

use ArrayIterator\Rev\Source\Application;
use ArrayIterator\Rev\Source\Benchmark\Timer;

/**
 * @var Application $app
 * @todo add hooks
 */
$app = require __DIR__ .'/../init.php';
$app->boot()->shutdown();
