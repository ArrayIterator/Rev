<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Relationship\Attributes;

use ArrayIterator\Rev\Source\Database\Mapping\AbstractEntity;
use ArrayIterator\Rev\Source\Database\Relationship\BelongsToMany as BelongsToManyRelationship;
use ArrayIterator\Rev\Source\Database\Relationship\RelationshipInterface;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS|Attribute::IS_REPEATABLE)]
class BelongsToMany implements RelationshipAttributeInterface
{
    public readonly array $pivot;

    public function __construct(
        public readonly string $property,
        public readonly string $model,
        public readonly ?string $foreignKey,
        public readonly ?string $ownerKey,
        string ...$pivot
    ) {
        $this->pivot = $pivot;
    }

    /**
     * @return string
     */
    public function getProperty(): string
    {
        return $this->property;
    }

    /**
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @return string|null
     */
    public function getForeignKey(): ?string
    {
        return $this->foreignKey;
    }

    /**
     * @return string|null
     */
    public function getOwnerKey(): ?string
    {
        return $this->ownerKey;
    }
    public function create(AbstractEntity $entity): RelationshipInterface
    {
        return new BelongsToManyRelationship($entity, $this);
    }
}
