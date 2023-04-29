<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Relationship;

use ArrayIterator\Rev\Source\Database\Mapping\AbstractEntity;
use ArrayIterator\Rev\Source\Database\Relationship\Attributes\RelationshipAttributeInterface;

interface RelationshipInterface
{
    public function __construct(AbstractEntity $entity, RelationshipAttributeInterface $relationshipAttribute);

    public function first() : ?AbstractEntity;

    /**
     * @return array<AbstractEntity>
     */
    public function all() : array;
}
