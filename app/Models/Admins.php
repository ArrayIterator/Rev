<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\App\Models;

use ArrayIterator\Rev\App\Entities\AdminEntity;
use ArrayIterator\Rev\Source\Database\Connection;
use ArrayIterator\Rev\Source\Database\Mapping\AbstractModel;
use ArrayIterator\Rev\Source\Database\Mapping\ResultSet;

class Admins extends AbstractModel
{
    protected string $table = 'admins';

    protected string $primaryKey = 'id';

    protected string $entityClass = AdminEntity::class;

    public static function find(float|int|string $id, ?Connection $connection = null): ResultSet
    {
        $obj = new static($connection);
        if (!is_numeric($id)) {
            if (filter_var($id, FILTER_VALIDATE_EMAIL)) {
                $obj->primaryKey = 'email';
            } else {
                $obj->primaryKey = 'username';
            }
        }
        $obj
            ->setDefaultMaxResults(1)
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->where(
                [
                    $obj->getPrimaryKey() => $id
                ]
            )->getResult();
        return $obj->getResult();
    }
}
