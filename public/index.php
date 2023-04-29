<?php
namespace ArrayIterator\Rev\Public;

use ArrayIterator\Rev\Source\Application;
use ArrayIterator\Rev\Source\Kernel;

(function () {
    /**
     * @var Application $app
     */
    require dirname(__DIR__) .'/vendor/autoload.php';
    $app = require dirname(__DIR__) .'/init.php';
    if (!$app instanceof Application) {
        $app = Kernel::application();
    }
    $app
        ->prepare()
        ->boot()
        ->shutdown();
})();
