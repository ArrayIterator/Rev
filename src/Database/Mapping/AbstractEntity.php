<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Mapping;

use ArrayIterator\Rev\Source\Database\Caster\ArrayCaster;
use ArrayIterator\Rev\Source\Database\Caster\Boolean;
use ArrayIterator\Rev\Source\Database\Caster\DateTimeCaster;
use ArrayIterator\Rev\Source\Database\Caster\FloatCaster;
use ArrayIterator\Rev\Source\Database\Caster\IntegerCaster;
use ArrayIterator\Rev\Source\Database\Caster\Interfaces\CasterInterface;
use ArrayIterator\Rev\Source\Database\Caster\JsonCaster;
use ArrayIterator\Rev\Source\Database\Caster\SerializeCaster;
use ArrayIterator\Rev\Source\Database\Caster\TextCaster;
use ArrayIterator\Rev\Source\Database\Relationship\Attributes\RelationshipAttributeInterface;
use ArrayIterator\Rev\Source\Database\Relationship\RelationshipInterface;
use JsonSerializable;
use ReflectionAttribute;
use ReflectionObject;
use Throwable;

abstract class AbstractEntity implements JsonSerializable
{
    protected bool $fromStatement = false;

    /**
     * @var string[]|CasterInterface[]
     */
    protected array $caster = [
        'text' => TextCaster::class,
        'string' => TextCaster::class,
        'serialize' => SerializeCaster::class,
        'int' => IntegerCaster::class,
        'integer' => IntegerCaster::class,
        'number' => IntegerCaster::class,
        'double' => FloatCaster::class,
        'float' => FloatCaster::class,
        'datetime' => DateTimeCaster::class,
        'date' => DateTimeCaster::class,
        'array' => ArrayCaster::class,
        'json' => JsonCaster::class,
        'boolean' => Boolean::class,
        'bool' => Boolean::class,
    ];

    /**
     * @var ?array<string, RelationshipInterface>
     */
    private ?array $relationshipAttributes = null;

    protected array $objectCaster = [];

    protected array $lowerKeys = [];

    protected array $changeKeys = [];

    protected array $originalData = [];

    protected array $castedData = [];

    protected array $changedData = [];

    protected array $allowedFieldChange = [];

    private array $allowedFieldChangeKeys;

    protected array $cast = [];

    protected AbstractModel $model;

    private function __construct(AbstractModel $model)
    {
        $this->model = $model;
        $keys = array_filter($this->allowedFieldChange, 'is_string');
        $this->allowedFieldChangeKeys = array_fill_keys(
            array_keys(array_change_key_case(array_flip($keys))),
            true
        );

        $this->initAttributes();
    }

    private function initAttributes(): void
    {
        $this->relationshipAttributes ??= [];
        $relations = (new ReflectionObject($this))->getAttributes(
            RelationshipAttributeInterface::class,
            ReflectionAttribute::IS_INSTANCEOF
        );
        foreach ($relations as $relation) {
            try {
                $relation = $relation->newInstance();
                /**
                 * @var RelationshipAttributeInterface $relation
                 */
                $this->relationshipAttributes[$relation->getProperty()] = $relation->create($this);
            } catch (Throwable) {
            }
        }
    }

    /**
     * @param string $type
     * @return ?CasterInterface
     */
    public function getCaster(string $type): ?CasterInterface
    {
        $type = trim($type);
        if (!isset($this->caster[$type])) {
            $lower = strtolower($type);
            $caster = $this->caster[$lower]??null;
            if ($caster === null) {
                return null;
            }
            $type = $lower;
        }
        if (!isset($this->caster[$type])) {
            return null;
        }
        $lowerCaster = strtolower($this->caster[$type]);
        $this->objectCaster[$lowerCaster] ??= new $this->caster[$type]($this->model->getConnection());
        return $this->objectCaster[$lowerCaster];
    }

    protected function cast(string $name, $data) : mixed
    {
        $caster = $this->cast[$name]??null;
        if ($caster === null) {
            $lowerName = strtolower($name);
            $key = $this->lowerKeys[$lowerName]??null;
            $caster = $this->cast[$key]??$this->cast[$lowerName]??null;
        }

        if (!$caster) {
            return $data;
        }

        if ($caster instanceof CasterInterface) {
            return $caster->cast($data, ['name' => $name]);
        }

        if (!is_string($caster)) {
            return $data;
        }

        preg_match('~^([?]+)?\s*(.+)$~', $caster, $match);
        if (empty($match)) {
            return $data;
        }
        if (!empty($match[1]) && $data === null) {
            return null;
        }
        if (is_subclass_of($caster, CasterInterface::class)) {
            $this->cast[$name] = new $caster($this->model->getConnection());
            return $this->cast[$name]->cast($data, ['name' => $name]);
        }
        $caster = $match[2];
        $caster = $this->getCaster($caster);
        if (!$caster) {
            return $data;
        }
        $this->cast[$name] = $caster;
        return $caster->cast($data, ['name' => $name]);
    }

    public function toArray() : array
    {
        foreach ($this->originalData as $key => $item) {
            if (!array_key_exists($key, $this->castedData)) {
                $this->castedData[$key] = $this->cast($key, $item);
            }
        }
        return $this->castedData;
    }

    public function __set(string $name, $value): void
    {
        $methods = [
            str_replace('-', '_', $name),
            str_replace(['_', '_'], '', $name)
        ];

        if ($this->relationshipAttributes === null) {
            $this->fromStatement = true;
            foreach ($methods as $method) {
                if (method_exists($this, "set$method")) {
                    $this->{"set$method"}($value);
                    return;
                }
            }
            $this->lowerKeys[strtolower($name)] = $name;
            $this->originalData[$name] = $value;
            return;
        }

        $lowerName = strtolower($name);
        $key = $this->lowerKeys[$lowerName]??null;
        if ($key !== null && $key !== $name) {
            foreach ($methods as $method) {
                if (method_exists($this, "set$method")) {
                    $this->{"set$method"}($value);
                    return;
                }
            }
        }

        if (($this->originalData[$name]??null) === $value) {
            $changeKey = $this->changeKeys[$lowerName] ?? null;
            if ($changeKey) {
                unset($this->changedData[$changeKey], $this->changeKeys[$lowerName]);
                return;
            }
        }
        if (!isset($this->allowedFieldChangeKeys[$lowerName])) {
            return;
        }
        $this->changeKeys[$lowerName] = $key??$name;
        $this->changedData[$name] = $value;
    }

    public function __get(string $name)
    {
        $methods = [
            str_replace('-', '_', $name),
            str_replace(['_', '_'], '', $name)
        ];
        foreach ($methods as $method) {
            if (method_exists($this, "get$method")) {
                return $this->{"get$method"}();
            }
        }

        $lowerName = strtolower($name);
        $key = $this->lowerKeys[$lowerName]??null;
        if ($key === null) {
            if (isset($this->relationshipAttributes[$name])) {
                return $this->relationshipAttributes[$name];
            }
            return null;
        }

        $this->castedData[$key] = $this->cast(
            $name,
            $this->originalData[$key]
        );

        return $this->castedData[$key];
    }

    /**
     * @return bool
     */
    public function isFromStatement(): bool
    {
        return $this->fromStatement;
    }

    /**
     * @return array
     */
    public function getRelationshipAttributes(): array
    {
        return $this->relationshipAttributes;
    }

    /**
     * @return array
     */
    public function getChangedData(): array
    {
        return $this->changedData;
    }

    /**
     * @return AbstractModel
     */
    public function getModel(): AbstractModel
    {
        return $this->model;
    }

    public function get(string $name)
    {
        return $this->__get($name);
    }

    public function getOriginal(string $name)
    {
        return $this->originalData[$name]??null;
    }

    /**
     * @return array
     */
    public function getOriginalData(): array
    {
        return $this->originalData;
    }

    public function jsonSerialize(): array
    {
        return $this->originalData;
    }
}
