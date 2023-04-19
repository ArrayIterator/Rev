<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database;

use ArrayIterator\Rev\Source\Benchmark\Record;
use PDO;
use PDOException;
use PDOStatement;

class Statement extends PDOStatement
{
    /**
     * @var mixed|Record
     */
    private mixed $record;

    /**
     * @noinspection PhpSameParameterValueInspection
     */
    private function __construct(
        public readonly Connection $connection,
        $record = null
    ) {
        $this->record = $record;
    }

    /**
     * @param array|null $params
     * @return bool
     * @throws PDOException
     */
    public function execute(?array $params = null): bool
    {
        if (!property_exists($this, 'record')) {
            return parent::execute($params);
        }

        $record = $this->record;
        unset($this->record);
        try {
            return parent::execute($params);
        } finally {
            if (!$record instanceof Record) {
                $record->stop();
            }
        }
    }

    public function fetchNumeric() : array|false
    {
        return parent::fetch(PDO::FETCH_NUM);
    }

    public function fetchAllNumeric() : array|false
    {
        return parent::fetchAll(PDO::FETCH_NUM);
    }

    public function fetchAssociative() : array|false
    {
        return parent::fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAllAssociative() : array
    {
        return parent::fetchAll(PDO::FETCH_ASSOC);
    }
}
