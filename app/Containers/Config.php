<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\App\Containers;

use ArrayIterator\Rev\Source\Storage\ObjectContainer;
use ArrayIterator\Rev\Source\Utils\Filter\Consolidation;
use ArrayIterator\Rev\Source\Utils\Parser\DotEnv;
use Psr\Container\ContainerInterface;

class Config extends ObjectContainer
{
    public function getId(): string
    {
        return 'config';
    }

    public function __invoke(ContainerInterface $container): array
    {
        $config = [];
        if (file_exists(dirname(__DIR__, 2) .'/config.php')) {
            $config = include dirname(__DIR__, 2) .'/config.php';
        }
        $config = !is_array($config) ? [] : $config;
        $array = [];
        foreach (Consolidation::notationToArray(
            DotEnv::fromFile(
                dirname(__DIR__, 2) .'/.env',
                true,
                true
            )
        ) as $key => $value) {
            $array[strtolower($key)] = is_array($value)
                ? array_change_key_case($value, CASE_LOWER)
                : $value;
        }

        $array['database'] = $array['database']??[];
        $array['database'] = Database::filterConfig(
            !is_array($array['database']) ? [] : $array['database']
        );

        return array_replace_recursive($config, $array);
    }
}
