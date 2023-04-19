<?php
//ini_set('opcache.validate_timestamps', '1');
//ini_set('opcache.memory_consumption', '10M');
use ArrayIterator\Rev\Source\Kernel;

require __DIR__ .'/vendor/autoload.php';
return Kernel::prepare();