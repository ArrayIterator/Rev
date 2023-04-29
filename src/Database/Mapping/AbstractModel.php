<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Mapping;

use ArrayIterator\Rev\Source\Database\Connection;
use ArrayIterator\Rev\Source\Database\Exceptions\ModelException;
use ArrayIterator\Rev\Source\Database\Query\Builder;
use ArrayIterator\Rev\Source\Database\Query\Expression;
use Throwable;

abstract class AbstractModel
{
    private static ?Connection $defaultConnection = null;

    protected Connection $connection;

    protected int $defaultMaxResults = 100000;

    protected string $table = '';

    protected string $primaryKey = '';

    protected string $entityClass = ValuesEntity::class;

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
        $this->resetQueryBuilder();
    }

    public function resetQueryBuilder(): void
    {
        $this->builder = new Builder($this->getConnection());
        $this
            ->builder
            ->select('*')
            ->from($this->getTable())
            ->setMaxResults($this->getDefaultMaxResults());
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
    public function getEntityClass(): string
    {
        if (!is_subclass_of(
            $this->entityClass,
            AbstractEntity::class,
            true
        )) {
            $this->entityClass = ValuesEntity::class;
        }
        return $this->entityClass;
    }

    /**
     * @return Builder|null
     */
    public function getBuilder(): ?Builder
    {
        return $this->builder;
    }

    /**
     * @return int
     */
    public function getDefaultMaxResults(): int
    {
        return $this->defaultMaxResults;
    }

    /**
     * @param int $defaultMaxResults
     * @return AbstractModel
     */
    public function setDefaultMaxResults(int $defaultMaxResults): static
    {
        $this->defaultMaxResults = $defaultMaxResults;
        return $this;
    }

    /**
     * @throws Throwable
     */
    public static function find(int|string|float $id, ?Connection $connection = null): ResultSet
    {
        $obj = new static($connection);
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

    public static function findBy(
        array $where,
        array $params = [],
        ?Connection $connection = null,
        int $offset = 0,
        ?int $maxResults = null
    ): static {
        $obj = new static($connection);
        return $obj
            ->setFirstResult($offset)
            ->setDefaultMaxResults($maxResults)
            ->where($where, $params);
    }

    public function getResult() : ResultSet
    {
        return new ResultSet(
            $this,
            $this->getBuilder()
        );
    }

    public function setFirstResult(int $firstResult): static
    {
        $this->builder->setFirstResult($firstResult);
        return $this;
    }

    public function setMaxResults(?int $defaultMaxResults): static
    {
        $this->builder->setMaxResults($defaultMaxResults);
        return $this;
    }

    public function where(array $predicates, array $params = []): static
    {
        $this->builder->resetQueryPart('where');
        $this->builder->setParameters([]);

        return $this->andWhere($predicates, $params);
    }

    public function andWhere(array $predicates, array $params = []): static
    {
        $this->predicateCall($predicates, $params, 'andWhere');
        return $this;
    }

    public function orWhere(array $predicates, array $params = []): static
    {
        $this->predicateCall($predicates, $params, 'orWhere');
        return $this;
    }

    private function predicateCall(array $predicates, array $params, string $fn): void
    {
        foreach ($predicates as $key => $predicate) {
            if ($predicate instanceof Expression) {
                $this->builder->$fn($predicate);
                continue;
            }
            if (is_string($key)) {
                $prefix = $this->getConnection()->quoteIdentifier($key);
                $lowerKey = strtolower($key);
                $separator = (bool) preg_match('~!?=|\sin|not\s+in~', $lowerKey);
                $isNot = $separator && (
                        str_contains($lowerKey, 'not')
                        || str_contains($lowerKey, '!=')
                    );
                if ($predicate === null) {
                    $this->builder->$fn(
                        sprintf(
                            '%s IS %s NULL',
                            $isNot ? 'NOT' : '',
                            $prefix
                        )
                    );
                } elseif (is_array($predicate)) {
                    if (empty($predicate)) {
                        continue;
                    }
                    $array = [];
                    foreach ($predicate as $item) {
                        $array[] = $this->builder->createNamedParameter($item);
                    }
                    $query = sprintf(
                        '%s %s IN (%s)',
                        $prefix,
                        $isNot ? 'NOT' : '',
                        implode(', ', $array)
                    );
                    $this->builder->$fn($query);
                } else {
                    $placeholder = $this->builder->createNamedParameter(
                        $predicate
                    );
                    $this->builder->$fn(
                        sprintf(
                            '%s %s %s',
                            $prefix,
                            $isNot ? '!=' : '=',
                            $placeholder
                        )
                    );
                }
                continue;
            }

            $this->builder->orWhere($predicate);
        }

        foreach ($params as $key => $value) {
            $this->builder->setParameter($key, $value);
        }
    }

    public function groupBy(string ...$groupBy): static
    {
        $this->builder->groupBy(...$groupBy);
        return $this;
    }

    public function addGroupBy(string ...$groupBy): static
    {
        $this->builder->addGroupBy(...$groupBy);
        return $this;
    }

    public function having(array $having): static
    {
        $this->builder->having(...$having);
        return $this;
    }

    public function andHaving(array $having): static
    {
        $this->builder->andHaving(...$having);
        return $this;
    }

    public function orHaving(array $having): static
    {
        $this->builder->orHaving(...$having);
        return $this;
    }

    public function orderBy(string $sort, ?string $order = null): static
    {
        $this->builder->orderBy($sort, $order);
        return $this;
    }

    public function addOrderBy(string $sort, ?string $order = null): static
    {
        $this->builder->addOrderBy($sort, $order);
        return $this;
    }
}
