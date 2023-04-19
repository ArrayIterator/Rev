<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database;

use ArrayIterator\Rev\Source\Benchmark\Record;
use ArrayIterator\Rev\Source\Database\Query\Builder;
use ArrayIterator\Rev\Source\Traits\BenchmarkingTrait;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

/**
 * @mixin PDO
 */
class Connection
{
    use BenchmarkingTrait;

    protected ?PDO $pdo = null;

    protected array $currentOptions = [];

    protected ?DateTimeZone $databaseTimeZone = null;

    protected DateTimeZone $systemTimeZone;

    public function __construct(
        protected string $username,
        protected string $password,
        protected string $database,
        protected string $host = 'localhost',
        protected int $port = 3306,
        protected string $charset = 'utf8mb4',
        protected array $options = []
    ) {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->systemTimeZone = new DateTimeZone(date_default_timezone_get());
    }

    protected function getPdoConnection(): PDO
    {
        if ($this->pdo) {
            return $this->pdo;
        }
        $benchmark = $this->benchmarkStart(name: 'connection', group: 'database');
        $options = [
            PDO::MYSQL_ATTR_INIT_COMMAND => sprintf("SET NAMES '%s';", $this->charset),
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_STATEMENT_CLASS => [Statement::class, [$this]]
        ];
        foreach ($this->options as $key => $option) {
            if (!is_int($key) || isset($option[$key])) {
                continue;
            }
            $options[$key] = $option;
        }
        $this->currentOptions = array_filter($options, 'is_int', ARRAY_FILTER_USE_KEY);
        $dsn = sprintf(
            'mysql:%s;dbname=%s',
            str_starts_with($this->host, 'unix_socket:')
                ? $this->host
                : sprintf('host=%s;port=%d', $this->host, $this->port),
            $this->database
        );
        try {
            $this->pdo = new PDO(
                $dsn,
                $this->username,
                $this->password,
                $this->currentOptions
            );
            return $this->pdo;
        } finally {
            $benchmark->stop();
        }
    }

    public function compareDateToSQLTimezone(
        DateTimeInterface $from,
        DateTimeInterface $to
    ): string {
        $seconds = ($from->getTimestamp() - $to->getTimestamp());
        $hours = floor($seconds / 3600);
        $minutes = floor($seconds / 60 % 60);
        $hours = $hours < 10 && $hours >= 0
            ? "+0$hours"
            : ($hours < 0 && $hours > -10 ? "-0" . (-$hours) : "+$hours");
        $minutes = $minutes < 10 ? "0$minutes" : $minutes;
        return "$hours:$minutes";
    }

    /**
     * @return DateTimeZone
     */
    public function getSystemTimeZone(): DateTimeZone
    {
        return $this->systemTimeZone;
    }

    public function getDatabaseTimeZone(): DateTimeZone
    {
        if ($this->databaseTimeZone) {
            return $this->databaseTimeZone;
        }
        $this->databaseTimeZone = $this->getSystemTimeZone();
        $data = $this
            ->query(
                "SELECT CONVERT_TZ(NOW(), @@session.time_zone, '+00:00') as utc_session, now() as now"
            )
            ->fetch(PDO::FETCH_ASSOC);
        $utc_timezone = new DateTimeZone('UTC');
        try {
            $current = new DateTimeImmutable($data['now']);
            $utc_session = new DateTimeImmutable($data['utc_session'], $utc_timezone);
            $offset = self::compareDateToSQLTimezone($current, $utc_session);
            $this->databaseTimeZone = new DateTimeZone($offset);
        } catch (Exception) {
        }

        return $this->databaseTimeZone;
    }

    public function dateToUtc(DateTimeInterface $date) : ?DateTimeImmutable
    {
        if (!$date instanceof DateTimeImmutable) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $date = new DateTimeImmutable($date->format('c'));
        }

        return $date->getOffset() === 0 ? $date : $date->setTimezone(new DateTimeZone('UTC'));
    }

    public function convertDateToSystem(DateTimeInterface $date) : DateTimeImmutable
    {
        if (!$date instanceof DateTimeImmutable) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $date = new DateTimeImmutable($date->format('c'));
        }

        return $date->setTimezone($this->getSystemTimeZone());
    }

    public function convertDateToMysqlTimezone(DateTimeInterface $date) : DateTimeImmutable
    {
        if (!$date instanceof DateTimeImmutable) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $date = new DateTimeImmutable($date->format('c'));
        }

        return $date->setTimezone($this->getDatabaseTimeZone());
    }

    public function ping(): bool
    {
        return $this->exec('SELECT 1') !== false;
    }
    public function quoteIdentifier(string $param, bool $clean = true): string
    {
        $quotes = [];
        foreach (explode('.', $param) as $string) {
            if (trim($string) === '*') {
                $quotes[] = '*';
                continue;
            }
            if ($clean === true) {
                $length = strlen($string);
                if ($length > 2 && $string[0] === '`' && $string[$length - 1] === '`') {
                    $string = substr($string, 1, -1);
                }
            }
            $quotes[] = '`' . str_replace('`', '``', $string) . '`';
        }

        return implode('.', $quotes);
    }

    private function createBenchMarkRecord(string $query, string $type): Record
    {
        return $this->benchmarkStart(
            name: $type,
            context: [
                'type' => 'exec',
                'query' => $query,
            ],
            group: 'database'
        );
    }

    public function exec(string $query): false|int
    {
        $benchmark = $this->createBenchMarkRecord($query, 'exec');
        try {
            return $this->getPdoConnection()->exec($query);
        } finally {
            $benchmark->stop();
        }
    }

    public function query(string $query, ?int $fetchMode = null, ...$fetch_mode_args): PDOStatement
    {
        $benchmark = $this->createBenchMarkRecord($query, 'query');
        try {
            return $this->getPdoConnection()->query($query, $fetchMode, ...$fetch_mode_args);
        } finally {
            $benchmark->stop();
        }
    }

    public function unbufferedQuery(string $query, ?int $fetchMode = null, ...$fetch_mode_args): PDOStatement
    {
        $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $benchmark = $this->createBenchMarkRecord($query, 'query');
        try {
            return $this->getPdoConnection()->query($query, $fetchMode, ...$fetch_mode_args);
        } finally {
            $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            $benchmark->stop();
        }
    }

    public function prepare(string $query, array $options = []): false|Statement
    {
        $benchmark = $this->createBenchMarkRecord('prepare', $query);
        $options[PDO::ATTR_STATEMENT_CLASS] = [Statement::class, [$this, $benchmark]];
        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        return $this->getPdoConnection()->prepare(
            $query,
            $options
        );
    }

    public function unbufferedPrepare(string $query, array $options = []): false|Statement
    {
        $benchmark = $this->createBenchMarkRecord('prepare', $query);
        $options[PDO::ATTR_STATEMENT_CLASS] = [Statement::class, [$this, $benchmark]];
        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = false;
        return $this->getPdoConnection()->prepare(
            $query,
            $options
        );
    }

    public function setAttribute(int $attribute, mixed $value): bool
    {
        if ($attribute === PDO::ATTR_STATEMENT_CLASS) {
            $value = [Statement::class, [$this]];
        }
        if ($attribute === PDO::ATTR_ERRMODE) {
            $value = PDO::ERRMODE_EXCEPTION;
        }

        return $this->getPdoConnection()->setAttribute($attribute, $value);
    }

    public function createQueryBuilder() : Builder
    {
        return new Builder($this);
    }

    /**
     * @throws PDOException|Throwable
     */
    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([$this->getPdoConnection(), $name], $arguments);
    }

    public static function __callStatic(string $name, array $arguments)
    {
        return call_user_func_array([PDO::class, $name], $arguments);
    }
}
