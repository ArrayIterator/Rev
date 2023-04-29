<?php
namespace ArrayIterator\Rev\Public;

use ArrayIterator\Rev\App\Entities\AdminEntity;
use ArrayIterator\Rev\App\Models\Admins;
use ArrayIterator\Rev\App\Models\Options;
use ArrayIterator\Rev\Source\Application;
use ArrayIterator\Rev\Source\Database\Connection;
use ArrayIterator\Rev\Source\Database\Mapping\AbstractModel;

/**
 * @var Application $app
 * @todo add hooks
 */
$app = require __DIR__ .'/../init.php';
$connection = new Connection(
    'root',
    'mysql',
    'support'
);
AbstractModel::setDefaultConnection($connection);
/**
 * @var AdminEntity $option
 */
$option = Admins::find('admin1@example.com')->first();
//$option->toArray();
print_r($option->logs->all());
exit;
$app->boot()->shutdown();
