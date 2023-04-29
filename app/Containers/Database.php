<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\App\Containers;

use ArrayIterator\Rev\Source\Database\Connection;
use ArrayIterator\Rev\Source\Database\Mapping\AbstractModel;
use ArrayIterator\Rev\Source\Storage\ObjectContainer;
use PDO;
use Psr\Container\ContainerInterface;
use Throwable;

class Database extends ObjectContainer
{
    public function getId(): string
    {
        return 'database';
    }

    /**
     * @param array $databaseConfig
     * @return array{
     *     user: string,
     *     password: string,
     *     name: string,
     *     host: string,
     *     port: integer,
     *     charset: string,
     *     options: array,
     * }
     */
    public static function filterConfig(array $databaseConfig): array
    {
        $default = [
            'user' => '',
            'password' => '',
            'name' => '',
            'host' => 'localhost',
            'port' => 3306,
            'charset' => 'utf8mb4',
            'options' => [],
        ];

        $databaseConfig = array_merge($default, $databaseConfig);
        $databaseConfig['options'] = !is_array($databaseConfig['options'])
            ? []
            : $databaseConfig['options'];
        $options = [];
        $pdoClass = PDO::class;
        foreach ($databaseConfig['options'] as $key => $v) {
            if (is_string($key)) {
                if (!str_contains($key, 'ATTR')) {
                    $key = strtoupper($key);
                    $key = "ATTR_$key";
                }
                if (!defined("$pdoClass::$key")) {
                    continue;
                }
                $key = constant("$pdoClass::$key");
            }
            if (is_int($key)) {
                $options[$key] = $v;
            }
        }

        /**
         * @see PDO::ATTR_*
         */
        $databaseConfig['options'] = $options;
        foreach ($default as $key => $value) {
            settype($options[$key], gettype($value));
        }
        $databaseConfig['port'] = $databaseConfig['port']?:3306;
        return $databaseConfig;
    }

    public function __invoke(ContainerInterface $container): Connection
    {
        try {
            $databaseConfig = $container->get('config')['database'] ?? [];
            $databaseConfig = !is_array($databaseConfig)
                ? []
                : $databaseConfig;
        } catch (Throwable) {
            $databaseConfig = self::filterConfig([]);
        }

        $connection = new Connection(
            username: $databaseConfig['user'],
            password: $databaseConfig['password'],
            database: $databaseConfig['name'],
            host: $databaseConfig['host'],
            port: $databaseConfig['port'],
            charset: $databaseConfig['charset']?:'utf8mb4',
            options: $databaseConfig['options']
        );

        AbstractModel::setDefaultConnection($connection);
        return $connection;
    }
}
