<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Caster\Traits;

use ArrayIterator\Rev\Source\Database\Connection;

trait CasterTrait
{
    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param Connection $connection
     */
    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    public function value($data, array $params = [])
    {
        return $data;
    }
}
