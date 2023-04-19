<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Query;

use ArrayIterator\Rev\Source\Database\Connection;
use ArrayIterator\Rev\Source\Database\Statement;
use ArrayIterator\Rev\Source\Utils\Filter\DataType;
use Exception;
use JsonSerializable;
use PDOException;
use Serializable;
use Stringable;
use Throwable;

class Builder
{
    public const SELECT = 0;

    public const DELETE = 1;

    public const UPDATE = 2;

    public const INSERT = 3;

    public const STATE_DIRTY = 0;

    public const STATE_CLEAN = 1;

    /*
     * The default values of SQL parts collection
     */
    private const SQL_PARTS_DEFAULTS = [
        'select'   => [],
        'distinct' => false,
        'from'     => [],
        'join'     => [],
        'set'      => [],
        'where'    => null,
        'groupBy'  => [],
        'having'   => null,
        'orderBy'  => [],
        'values'   => [],
    ];

    private array $sqlParts = self::SQL_PARTS_DEFAULTS;

    /**
     * The complete SQL string for this query.
     */
    private ?string $sql = null;

    /**
     * The query parameters.
     *
     * @var list<mixed>|array<string, mixed>
     */
    private array $params = [];

    /**
     * The type of query this is. Can be select, update or delete.
     */
    private int $type = self::SELECT;

    /**
     * The state of the query object. Can be dirty or clean.
     */
    private int $state = self::STATE_CLEAN;

    /**
     * The index of the first result to retrieve.
     */
    private int $firstResult = 0;

    /**
     * The maximum number of results to retrieve or NULL to retrieve all results.
     */
    private ?int $maxResults = null;

    /**
     * The counter of bound parameters used with {@see bindValue).
     */
    private int $boundCounter = 0;

    /**
     * @var bool
     */
    private bool $selectLocked = false;

    public function __construct(public readonly Connection $connection)
    {
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public static function create(Connection $connection): static
    {
        return new static(connection: $connection);
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @throws Throwable
     */
    public function fetchAssociative(): array|bool
    {
        return $this->executeQuery()->fetchAssociative();
    }

    /**
     * @throws Throwable
     */
    public function fetchNumeric() : array|false
    {
        return $this->executeQuery()->fetchNumeric();
    }

    /**
     * @throws Throwable
     */
    public function fetchAllNumeric(): array
    {
        return $this->executeQuery()->fetchAllNumeric();
    }

    /**
     * @throws Throwable
     */
    public function fetchAllAssociative(): array
    {
        return $this->executeQuery()->fetchAllAssociative();
    }

    /**
     * Executes an SQL query (SELECT) and returns a Result.
     *
     * @throws Throwable
     */
    public function executeQuery(&$executed = null): Statement
    {
        $stmt = $this->connection->prepare(
            $this->getSQL()
        );
        $executed = $stmt->execute($this->getQuerySQLParameters());
        if (!$executed) {
            throw new PDOException(
                $stmt->errorInfo()[2],
                $stmt->errorInfo()[1],
            );
        }
        return $stmt;
    }

    /**
     * @throws Exception
     */
    public function executeUnbufferedQuery(): Statement
    {
        $stmt = $this->connection->unbufferedPrepare(
            $this->getSQL()
        );
        $executed = $stmt->execute($this->getQuerySQLParameters());
        if (!$executed) {
            throw new PDOException(
                $stmt->errorInfo()[2],
                $stmt->errorInfo()[1],
            );
        }
        return $stmt;
    }

    public function getQuerySQLParameters(): array
    {
        $params = [];
        foreach ($this->params as $key => $v) {
            if ($v === null || is_int($v) || is_float($v)) {
                $params[$key] = $v;
                continue;
            }
            if (is_bool($v)) {
                $params[$key] = $v ? 1 : 0;
                continue;
            }
            if ($v instanceof Serializable) {
                $params[$key] = serialize($v);
                continue;
            }
            if ($v instanceof JsonSerializable) {
                $params[$key] = json_encode($v);
                continue;
            }
            if ($v instanceof Stringable) {
                $params[$key] = (string) $v;
                continue;
            }
            $params[$key] = DataType::shouldSerialize(data: $v);
        }

        return $params;
    }

    /**
     * @throws Throwable
     */
    public function executeStatement(): int
    {
        return $this->executeQuery()->rowCount();
    }

    /**
     * @throws Throwable
     */
    public function execute() : Statement
    {
        return $this->executeQuery();
    }


    /**
     * @throws Exception
     */
    public function getSQL(): string
    {
        if ($this->sql !== null && $this->state === self::STATE_CLEAN) {
            return $this->sql;
        }

        $sql = match ($this->type) {
            self::INSERT => $this->getSQLForInsert(),
            self::DELETE => $this->getSQLForDelete(),
            self::UPDATE => $this->getSQLForUpdate(),
            default      => $this->getSQLForSelect(),
        };

        $this->state = self::STATE_CLEAN;
        $this->sql   = $sql;

        return $sql;
    }

    public function setParameter(string|int|float $key, $value): static
    {
        $this->params[$key] = $value;
        return $this;
    }

    public function setParameters(array $params): static
    {
        $this->params     = $params;
        return $this;
    }

    public function getParameters(): array
    {
        return $this->params;
    }

    public function getParameter(float|int|string $key): mixed
    {
        return $this->params[$key] ?? null;
    }

    public function setFirstResult(int $firstResult): static
    {
        $this->state       = self::STATE_DIRTY;
        $this->firstResult = $firstResult;

        return $this;
    }

    public function getFirstResult(): int
    {
        return $this->firstResult;
    }

    public function setMaxResults(?int $maxResults): static
    {
        $this->state      = self::STATE_DIRTY;
        $this->maxResults = $maxResults;

        return $this;
    }

    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    public function add(string $sqlPartName, mixed $sqlPart, bool $append = false): static
    {
        $isArray    = is_array($sqlPart);
        $isMultiple = is_array($this->sqlParts[$sqlPartName]);

        if ($isMultiple && ! $isArray) {
            $sqlPart = [$sqlPart];
        }

        $this->state = self::STATE_DIRTY;

        if ($append) {
            if ($sqlPartName === 'orderBy'
                || $sqlPartName === 'groupBy'
                || $sqlPartName === 'select'
                || $sqlPartName === 'set'
            ) {
                foreach ($sqlPart as $part) {
                    $this->sqlParts[$sqlPartName][] = $part;
                }
            } elseif ($isArray && is_array($sqlPart[key($sqlPart)])) {
                $key                                  = key($sqlPart);
                $this->sqlParts[$sqlPartName][$key][] = $sqlPart[$key];
            } elseif ($isMultiple) {
                $this->sqlParts[$sqlPartName][] = $sqlPart;
            } else {
                $this->sqlParts[$sqlPartName] = $sqlPart;
            }

            return $this;
        }

        $this->sqlParts[$sqlPartName] = $sqlPart;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSelectLocked(): bool
    {
        return $this->selectLocked;
    }

    /**
     * @param bool $selectLocked
     *
     * @return $this
     */
    public function setSelectLocked(bool $selectLocked): static
    {
        $this->selectLocked = $selectLocked;
        return $this;
    }

    public function select(string|int ...$selects): static
    {
        $this->type = self::SELECT;
        if (!empty($this->getQueryPart(queryPartName: 'select')) && $this->isSelectLocked()) {
            return $this;
        }

        if (count($selects) === 0) {
            return $this;
        }

        return $this->add('select', $selects);
    }

    public function distinct(): static
    {
        $this->sqlParts['distinct'] = true;

        return $this;
    }

    public function addSelect(string ...$selects): static
    {
        $this->type = self::SELECT;
        if (!empty($this->getQueryPart(queryPartName: 'select')) && $this->isSelectLocked()) {
            return $this;
        }
        if (empty($selects)) {
            return $this;
        }
        return $this->add(sqlPartName: 'select', sqlPart: $selects, append: true);
    }

    public function delete(?string $delete = null, ?string $alias = null): static
    {
        $this->type = self::DELETE;

        if ($delete === null) {
            return $this;
        }

        return $this->add(sqlPartName: 'from', sqlPart: [
            'table' => $delete,
            'alias' => $alias,
        ]);
    }

    public function update(?string $update = null, ?string $alias = null): static
    {
        $this->type = self::UPDATE;

        if ($update === null) {
            return $this;
        }

        return $this->add(sqlPartName: 'from', sqlPart: [
            'table' => $update,
            'alias' => $alias,
        ]);
    }

    public function insert(?string $insert = null): static
    {
        $this->type = self::INSERT;

        if ($insert === null) {
            return $this;
        }

        return $this->add(sqlPartName: 'from', sqlPart: ['table' => $insert]);
    }

    public function from(string $from, ?string $alias = null): static
    {
        return $this->add(sqlPartName: 'from', sqlPart: [
            'table' => $from,
            'alias' => $alias,
        ], append: true);
    }

    public function join(
        string $fromAlias,
        string $join,
        string $alias,
        ?string $condition = null
    ): static {
        return $this->innerJoin(fromAlias: $fromAlias, join: $join, alias: $alias, condition: $condition);
    }

    public function innerJoin(
        string $fromAlias,
        string $join,
        string $alias,
        ?string $condition = null
    ): static {
        return $this->add(sqlPartName: 'join', sqlPart: [
            $fromAlias => [
                'joinType'      => 'inner',
                'joinTable'     => $join,
                'joinAlias'     => $alias,
                'joinCondition' => $condition,
            ],
        ], append: true);
    }

    public function leftJoin(
        string $fromAlias,
        string $join,
        string $alias,
        ?string $condition = null
    ) : static {
        return $this->add(sqlPartName: 'join', sqlPart: [
            $fromAlias => [
                'joinType'      => 'left',
                'joinTable'     => $join,
                'joinAlias'     => $alias,
                'joinCondition' => $condition,
            ],
        ], append: true);
    }

    public function rightJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): static
    {
        return $this->add(sqlPartName: 'join', sqlPart: [
            $fromAlias => [
                'joinType'      => 'right',
                'joinTable'     => $join,
                'joinAlias'     => $alias,
                'joinCondition' => $condition,
            ],
        ], append: true);
    }

    public function set(string $key, string|int|float $value): static
    {
        return $this->add(
            sqlPartName: 'set',
            sqlPart: $this->quoteIdentifierColumnTable($key) . ' = ' . $value,
            append: true
        );
    }

    public function where(string|Expression ...$predicates): static
    {
        if (count($predicates) === 0) {
            $this->resetQueryPart(queryPartName: 'where');
            return $this;
        }
        if (count($predicates) > 1) {
            $this->resetQueryPart(queryPartName: 'where');
            return $this->andWhere(...$predicates);
        }
        return $this->add(sqlPartName: 'where', sqlPart: reset($predicates));
    }

    public function andWhere(string|Expression ...$predicates): static
    {
        if (count($predicates) === 0) {
            return $this;
        }

        $where = $this->getQueryPart(queryPartName: 'where');
        if ($where instanceof Expression && $where->getType() === Expression::TYPE_AND) {
            $where = $where->with(...$predicates);
        } else {
            if ($where !== null) {
                array_unshift($predicates, $where);
            }
            $where = Expression::and(...$predicates);
        }
        return $this->add(sqlPartName: 'where', sqlPart: $where, append: true);
    }

    public function orWhere(string|Expression ...$predicates): static
    {
        if (count($predicates) === 0) {
            return $this;
        }

        $where = $this->getQueryPart('where');
        if ($where instanceof Expression && $where->getType() === Expression::TYPE_OR) {
            $where = $where->with(...$predicates);
        } else {
            if ($where !== null) {
                array_unshift($predicates, $where);
            }
            $where = Expression::or(...$predicates);
        }

        return $this->add(sqlPartName: 'where', sqlPart: $where, append: true);
    }

    public function groupBy(string ...$groupBy): static
    {
        if (count($groupBy) === 0) {
            return $this;
        }

        return $this->add(sqlPartName: 'groupBy', sqlPart: $groupBy);
    }

    public function addGroupBy(string ...$groupBy): static
    {
        if (count($groupBy) === 0) {
            return $this;
        }

        return $this->add(sqlPartName: 'groupBy', sqlPart: $groupBy, append: true);
    }

    public function setValue(string $column, string|int|float $value): static
    {
        $this->sqlParts['values'][$column] = $value;

        return $this;
    }

    public function values(array $values): static
    {
        return $this->add(sqlPartName: 'values', sqlPart: $values);
    }

    public function having(string|Expression ...$having): static
    {
        if (count($having) === 0) {
            return $this;
        }
        if (! (count($having) === 1 && reset($having) instanceof Expression)) {
            $having = Expression::and(...$having);
        }

        return $this->add(sqlPartName: 'having', sqlPart: $having);
    }

    public function andHaving(string|Expression ...$having): static
    {
        $args   = $having;
        $having = $this->getQueryPart(queryPartName: 'having');

        if ($having instanceof Expression && $having->getType() === Expression::TYPE_AND) {
            $having = $having->with(...$args);
        } else {
            array_unshift($args, $having);
            $having = Expression::and(...$args);
        }

        return $this->add(sqlPartName: 'having', sqlPart: $having);
    }

    public function orHaving(string|Expression ...$having): static
    {
        $args   = $having;
        $having = $this->getQueryPart(queryPartName: 'having');

        if ($having instanceof Expression && $having->getType() === Expression::TYPE_OR) {
            $having = $having->with(...$args);
        } else {
            array_unshift($args, $having);
            $having = Expression::or(...$args);
        }

        return $this->add(sqlPartName: 'having', sqlPart: $having);
    }

    public function orderBy(string $sort, ?string $order = null): static
    {
        return $this->add(sqlPartName: 'orderBy', sqlPart: $sort . ' ' . ($order ?? 'ASC'));
    }

    public function addOrderBy(string $sort, ?string $order = null): static
    {
        return $this->add(sqlPartName: 'orderBy', sqlPart: $sort . ' ' . ($order ?? 'ASC'), append: true);
    }

    public function getQueryPart(string $queryPartName)
    {
        return $this->sqlParts[$queryPartName]??null;
    }

    public function getQueryParts(): array
    {
        return $this->sqlParts;
    }

    public function resetQueryParts(?array $queryPartNames = null): static
    {
        $queryPartNames ??= array_keys($this->sqlParts);

        foreach ($queryPartNames as $queryPartName) {
            $this->resetQueryPart(queryPartName: $queryPartName);
        }

        return $this;
    }

    public function resetQueryPart(string $queryPartName): static
    {
        if (!isset(self::SQL_PARTS_DEFAULTS[$queryPartName])) {
            return $this;
        }
        $this->sqlParts[$queryPartName] = self::SQL_PARTS_DEFAULTS[$queryPartName];

        $this->state = self::STATE_DIRTY;

        return $this;
    }

    /**
     * @throws Exception
     */
    private function getSQLForSelect(): string
    {
        $query = 'SELECT '
                 . ($this->sqlParts['distinct'] ? 'DISTINCT ' : '')
                 . implode(
                     ', ',
                     array_map([$this, 'quoteIdentifierColumnTable'], $this->sqlParts['select'])
                 );

        $query .= ($this->sqlParts['from'] ? ' FROM ' . implode(', ', $this->getFromClauses()) : '')
                  . ($this->sqlParts['where'] !== null ? ' WHERE ' . ((string) $this->sqlParts['where']) : '')
                  . ($this->sqlParts['groupBy'] ? ' GROUP BY ' . implode(', ', $this->sqlParts['groupBy']) : '')
                  . ($this->sqlParts['having'] !== null ? ' HAVING ' . ((string) $this->sqlParts['having']) : '')
                  . ($this->sqlParts['orderBy'] ? ' ORDER BY ' . implode(', ', $this->sqlParts['orderBy']) : '');

        if ($this->isLimitQuery()) {
            if ($this->maxResults !== null) {
                $query .= " LIMIT $this->maxResults";
            }
            if ($this->firstResult > 0) {
                $query .= " OFFSET $this->firstResult";
            }
        }

        return $query;
    }

    /**
     * @return array<string>
     * @throws Exception
     */
    private function getFromClauses(): array
    {
        $fromClauses  = [];
        $knownAliases = [];

        // Loop through all FROM clauses
        foreach ($this->sqlParts['from'] as $from) {
            $tableSql = $this->realTableNameQuote(tableName: $from['table']);
            if ($from['alias'] === null) {
                $tableReference = $from['table'];
            } else {
                $tableReference = $from['alias'];
                $tableSql = $tableSql
                  . ' as '
                  . $this->quoteIdentifierColumnTable(tableName: $tableReference);
            }

            $knownAliases[$tableReference] = true;

            $fromClauses[$tableReference] = $tableSql
                . $this->getSQLForJoins(fromAlias: $tableReference, knownAliases: $knownAliases);
        }

        $this->verifyAllAliasesAreKnown(knownAliases: $knownAliases);

        return $fromClauses;
    }

    /**
     * @param array<string,true> $knownAliases
     *
     * @throws Exception
     */
    private function verifyAllAliasesAreKnown(array $knownAliases): void
    {
        foreach ($this->sqlParts['join'] as $fromAlias => $joins) {
            if (! isset($knownAliases[$fromAlias])) {
                throw new Exception(
                    sprintf('Alias of %s has not exist.', $fromAlias)
                );
            }
        }
    }

    private function isLimitQuery(): bool
    {
        return $this->maxResults !== null || $this->firstResult !== 0;
    }

    /**
     * Converts this instance into an INSERT string in SQL.
     */
    private function getSQLForInsert(): string
    {
        $columns = [];
        foreach (array_keys($this->sqlParts['values']) as $column) {
            $columns[] = $this->quoteIdentifierColumnTable($column);
        }
        return 'INSERT INTO '
               . $this->realTableNameQuote(tableName: $this->sqlParts['from']['table'])
               . ' (' . implode(', ', $columns) . ')' .
               ' VALUES(' . implode(', ', $this->sqlParts['values']) . ')';
    }

    /**
     * Converts this instance into an UPDATE string in SQL.
     */
    private function getSQLForUpdate(): string
    {
        $table = $this->realTableNameQuote(tableName: $this->sqlParts['from']['table'])
                 . ($this->sqlParts['from']['alias'] ? ' ' . $this->sqlParts['from']['alias'] : '');
        return 'UPDATE ' . $table
               . ' SET ' . implode(', ', $this->sqlParts['set'])
               . ($this->sqlParts['where'] !== null ? ' WHERE ' . ((string) $this->sqlParts['where']) : '');
    }

    /**
     * Converts this instance into a DELETE string in SQL.
     */
    private function getSQLForDelete(): string
    {
        $table = $this->realTableNameQuote(tableName: $this->sqlParts['from']['table'])
                 . ($this->sqlParts['from']['alias'] ? ' ' . $this->sqlParts['from']['alias'] : '');

        return 'DELETE '
               .'FROM ' . $table
               . ($this->sqlParts['where'] !== null ? ' WHERE ' . ((string) $this->sqlParts['where']) : '');
    }

    /**
     * Gets a string representation of this QueryBuilder which corresponds to
     * the final SQL query being constructed.
     *
     * @return string The string representation of this QueryBuilder.
     * @throws Exception
     */
    public function __toString()
    {
        return $this->getSQL();
    }

    public function createNamedParameter($value, ?string $placeHolder = null): string
    {
        if ($placeHolder === null) {
            $this->boundCounter++;
            $placeHolder = ':dcValue' . $this->boundCounter;
        }

        $this->setParameter(key: substr($placeHolder, 1), value: $value);

        return $placeHolder;
    }

    public function createPositionalParameter($value): string
    {
        $this->setParameter(key: $this->boundCounter, value: $value);
        $this->boundCounter++;

        return '?';
    }

    private function realTableNameQuote(string $tableName): string
    {
        return $this->quoteIdentifierColumnTable(tableName: $tableName);
    }

    private function quoteIdentifierColumnTable($tableName): float|int|string
    {
        return is_numeric($tableName)
            || ! is_string($tableName)
            || trim($tableName) === '*'
            ? $tableName
            : $this->connection->quoteIdentifier($tableName);
    }

    /**
     * @throws Exception
     */
    private function getSQLForJoins(string $fromAlias, array &$knownAliases): string
    {
        $sql = '';
        if (isset($this->sqlParts['join'][$fromAlias])) {
            foreach ($this->sqlParts['join'][$fromAlias] as $join) {
                if (array_key_exists($join['joinAlias'], $knownAliases)) {
                    throw new Exception(
                        sprintf('Alias "%s" is not unique.', $join['joinAlias'])
                    );
                }

                $joinAlias = $join['joinAlias'];
                $sql .= ' '
                        . strtoupper($join['joinType'])
                        . ' JOIN '
                        . $this->realTableNameQuote(tableName: $join['joinTable'])
                        . ' '
                        . $this->quoteIdentifierColumnTable(tableName: $joinAlias);
                if ($join['joinCondition'] !== null) {
                    $sql .= ' ON ' . $join['joinCondition'];
                }

                $knownAliases[$joinAlias] = true;
            }

            foreach ($this->sqlParts['join'][$fromAlias] as $join) {
                $sql .= $this->getSQLForJoins(fromAlias: $join['joinAlias'], knownAliases: $knownAliases);
            }
        }

        return $sql;
    }

    public function __clone()
    {
        foreach ($this->sqlParts as $part => $elements) {
            if (is_array($this->sqlParts[$part])) {
                foreach ($this->sqlParts[$part] as $idx => $element) {
                    if (! is_object($element)) {
                        continue;
                    }

                    $this->sqlParts[$part][$idx] = clone $element;
                }
            } elseif (is_object($elements)) {
                $this->sqlParts[$part] = clone $elements;
            }
        }

        foreach ($this->params as $name => $param) {
            if (! is_object($param)) {
                continue;
            }

            $this->params[$name] = clone $param;
        }
    }
}
