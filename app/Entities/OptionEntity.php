<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\App\Entities;

use ArrayIterator\Rev\Source\Database\Mapping\AbstractEntity;

class OptionEntity extends AbstractEntity
{
    protected array $cast = [
        'id' => 'int',
        'autoload' => 'boolean',
        'value' => '?serialize',
    ];

    protected array $allowedFieldChange = [
        'autoload',
        'value'
    ];
}
