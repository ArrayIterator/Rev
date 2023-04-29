<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\App\Entities;

use ArrayIterator\Rev\Source\Database\Caster\DateTimeCaster;
use ArrayIterator\Rev\Source\Database\Mapping\AbstractEntity;

class AdminLogEntity extends AbstractEntity
{
    protected array $cast = [
        'id' => 'int',
        'value' => 'serialize',
        'created_at' => 'datetime',
        'updated_at' => '?datetime',
    ];

    protected array $allowedFieldChange = [
        'admin_id',
        'name',
        'type',
        'value'
    ];
}
