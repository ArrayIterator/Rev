<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\App\Entities;

use ArrayIterator\Rev\App\Models\AdminLogs;
use ArrayIterator\Rev\Source\Database\Caster\DateTimeCaster;
use ArrayIterator\Rev\Source\Database\Mapping\AbstractEntity;
use ArrayIterator\Rev\Source\Database\Relationship\Attributes\OneToMany;
use ArrayIterator\Rev\Source\Database\Relationship\OneToMany as OneToManyRelation;

/**
 * @property-read OneToManyRelation $logs
 */
#[OneToMany('logs', AdminLogs::class, 'admin_id', 'id')]
class AdminEntity extends AbstractEntity
{
    protected array $cast = [
        'id' => 'int',
        'created_at' => DateTimeCaster::class,
        'updated_at' => '?datetime',
        'deleted_at' => '?datetime',
    ];

    protected array $allowedFieldChange = [
        'username',
        'email',
        'password',
        'type',
        'status',
        'first_name',
        'last_name',
        'security_key',
        'deleted_at',
    ];
}
