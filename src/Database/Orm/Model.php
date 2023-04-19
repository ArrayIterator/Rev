<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Orm;

use ArrayIterator\Rev\Source\Database\Connection;
use ArrayIterator\Rev\Source\Database\Exceptions\ModelException;
use ArrayIterator\Rev\Source\Database\Query\Builder;
use ArrayIterator\Rev\Source\Database\Query\Expression;
use stdClass;

class Model
{
    private static ?Connection $defaultConnection = null;

    protected Connection $connection;

    protected string $table = '';

    protected string $primaryKey = '';

    protected string $return = 'array';

    protected Builder $builder;

    /**
     * @return ?Connection
     */
    public static function getDefaultConnection(): ?Connection
    {
        return self::$defaultConnection;
    }

    /**
     * @param Connection $defaultConnection
     */
    public static function setDefaultConnection(Connection $defaultConnection): void
    {
        self::$defaultConnection = $defaultConnection;
    }

    final public function __construct(?Connection $connection = null)
    {
        self::$defaultConnection ??= $connection;
        $connection ??= self::getDefaultConnection();
        if (!$connection) {
            throw new ModelException(
                $this,
                'Model does not have valid connection'
            );
        }
        $this->connection = $connection;
        $this->builder = new Builder($this->getConnection());
        $this->builder->select('*');
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        if (($table = trim($this->table)) === '') {
            throw new ModelException(
                $this,
                'Model does not declare the table'
            );
        }
        return $table;
    }

    /**
     * @return string
     */
    public function getPrimaryKey(): string
    {
        if (($primaryKey = trim($this->primaryKey)) === '') {
            throw new ModelException(
                $this,
                'Model does not have primary key'
            );
        }

        return $primaryKey;
    }

    /**
     * @return string
     */
    public function getReturn(): string
    {
        return $this->return;
    }

    /**
     * @return Builder|null
     */
    public function getBuilder(): ?Builder
    {
        return $this->builder;
    }

    protected function convertReturn(array $data)
    {
        $return = $this->getReturn();
        $returnLower = strtolower($return);
        if ($returnLower === 'array') {
            return $data;
        }
        if ($returnLower == 'object') {
            $result = new stdClass();
        } elseif (class_exists($return)) {
            $result = new $return;
        } else {
            throw new ModelException(
                $this,
                'Object return class name of: "%s" is invalid.'
            );
        }
        foreach ($data as $key => $item) {
            $return->{$key} = $item;
        }
        return $result;
    }

    public function find(int|string|float $id)
    {
        $this->builder = $this
            ->getConnection()
            ->createQueryBuilder()
            ->select('*')
            ->from($this->getTable());
        $placeholder = $this->builder
            ->createNamedParameter(
                $this->getConnection()->quoteIdentifier($this->getPrimaryKey())
            );
        $this->builder->setParameter($placeholder, $id);
        $stmt = $this->builder->executeQuery();
        $result = $stmt->fetchAssociative();
        $stmt->closeCursor();
        if ($result === false) {
            return false;
        }
        return $this->convertReturn($result);
    }

    public function where(string|Expression ...$predicates): static
    {
        $this->builder = $this
            ->getConnection()
            ->createQueryBuilder()
            ->select('*')
            ->from($this->getTable())
            ->where(...$predicates);
        return $this;
    }
}
