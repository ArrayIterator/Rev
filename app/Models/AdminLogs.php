<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\App\Models;

use ArrayIterator\Rev\App\Entities\AdminLogEntity;
use ArrayIterator\Rev\Source\Database\Mapping\AbstractModel;

class AdminLogs extends AbstractModel
{
    protected string $table = 'admin_logs';

    protected string $primaryKey = 'id';

    protected string $entityClass = AdminLogEntity::class;
}
