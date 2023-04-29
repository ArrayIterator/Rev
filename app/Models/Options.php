<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\App\Models;

use ArrayIterator\Rev\App\Entities\OptionEntity;
use ArrayIterator\Rev\Source\Database\Mapping\AbstractModel;
use ArrayIterator\Rev\Source\Database\Relationship\Attributes\OneToMany;

class Options extends AbstractModel
{
    protected string $table = 'options';

    protected string $primaryKey = 'name';

    protected string $entityClass = OptionEntity::class;
}
