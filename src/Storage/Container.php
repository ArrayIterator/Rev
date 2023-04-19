<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Storage;

use ArrayIterator\Rev\Source\Storage\Exceptions\ContainerFrozenException;
use ArrayIterator\Rev\Source\Storage\Exceptions\ContainerNotFoundException;
use ArrayIterator\Rev\Source\Traits\BenchmarkingTrait;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    use BenchmarkingTrait;

    private array $container = [];

    private array $arguments = [];

    private array $raw = [];

    private array $frozen = [];

    /**
     * @param string $id
     * @return void
     * @throws ContainerFrozenException
     */
    private function assertFrozen(string $id): void
    {
        if (isset($this->frozen[$id])) {
            throw new ContainerFrozenException(
                sprintf('Container %s has frozen', $id)
            );
        }
    }

    /**
     * @throws ContainerFrozenException
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function set(string $id, callable $container, ...$arguments)
    {
        $this->assertFrozen($id);
        unset($this->raw[$id]);
        $this->container[$id] = $container;
        $this->arguments[$id] = $arguments;
    }

    /**
     * @param string $id
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function remove(string $id)
    {
        unset($this->frozen[$id], $this->raw[$id], $this->arguments[$id]);
    }

    /**
     * @throws ContainerFrozenException
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function raw(string $id, $raw)
    {
        $this->assertFrozen($id);
        $this->raw[$id] = $raw;
    }

    public function get(string $id)
    {
        if (array_key_exists($id, $this->raw)) {
            $this->frozen[$id] ??= true;
            return $this->raw[$id];
        }

        if (array_key_exists($id, $this->container)) {
            // @start
            $benchmark = $this->benchmarkStart(name: "id:$id", group: 'container');
            try {
                $value = $this->container[$id](...($this->arguments[$id] ?? []));
                unset($this->container[$id], $this->arguments[$id]);
                $this->raw($id, $value);
                $this->frozen[$id] = true;
                return $value;
            } finally {
                // @stop
                $benchmark->stop();
            }
        }

        throw new ContainerNotFoundException(
            sprintf('Container %s has not found', $id)
        );
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->raw) || array_key_exists($id, $this->container);
    }
}
