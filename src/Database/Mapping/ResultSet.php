<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Mapping;

use ArrayIterator\Rev\Source\Database\Query\Builder;
use ArrayIterator\Rev\Source\Database\Statement;
use Countable;
use PDO;
use Throwable;

class ResultSet implements Countable
{
    protected ?Statement $stmt = null;

    protected ?int $count = null;

    protected Builder $builder;

    /**
     * @var array<AbstractEntity>
     */
    protected array $data = [];

    public function __construct(
        public AbstractModel $model,
        Builder              $builder
    ) {
        $this->builder = clone $builder;
    }

    /**
     * @throws Throwable
     */
    protected function getStmt(): Statement
    {
        $this->stmt ??= $this->builder->executeQuery();
        $this->stmt->setFetchMode(
            PDO::FETCH_CLASS,
            $this->model->getEntityClass(),
            [$this->model]
        );
        $this->count ??= $this->stmt->rowCount();
        return $this->stmt;
    }

    /**
     * @throws Throwable
     */
    public function count(): int
    {
        $stmt = $this->getStmt();
        $this->count ??= $stmt->rowCount();
        return $this->count;
    }

    /**
     * @return AbstractModel
     */
    public function getModel(): AbstractModel
    {
        return $this->model;
    }

    /**
     * @return Builder
     */
    public function getBuilder(): Builder
    {
        return clone $this->builder;
    }

    /**
     * @throws Throwable
     */
    public function fetch(): ?AbstractEntity
    {
        $stmt = $this->getStmt();
        $count = count($this);
        if (count($this->data) >= $count) {
            return null;
        }
        $res = $stmt->fetch(PDO::FETCH_CLASS);
        if ($res === false) {
            return null;
        }
        $this->data[] = $res;
        return $res;
    }

    /**
     * @throws Throwable
     */
    public function first() : ?AbstractEntity
    {
        if (count($this) === 0) {
            return null;
        }
        if (!empty($this->data)) {
            return reset($this->data);
        }

        return $this->fetch();
    }

    /**
     * @throws Throwable
     */
    public function offset(int $pos)
    {
        if ($pos < 0 || count($this) < $pos) {
            return false;
        }
        if (isset($this->data[$pos])) {
            return $this->data[$pos];
        }

        while (($row = $this->fetch()) !== false) {
            if (count($this->data) === $pos) {
                return $row;
            }
        }

        return false;
    }

    /**
     * @throws Throwable
     */
    public function fetchAll(): array
    {
        $stmt = $this->getStmt();
        $count = count($this);
        if ($count === 0) {
            return [];
        }
        if (empty($this->data)) {
            $this->data = $stmt->fetchAll(
                PDO::FETCH_CLASS,
                $this->model->getEntityClass(),
                [$this->model]
            );
        } else {
            while (($this->fetch()) !== false) {
                // pass
            }
        }
        return $this->data;
    }
}
