<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Relationship\Attributes;

use ArrayIterator\Rev\Source\Database\Mapping\AbstractEntity;
use ArrayIterator\Rev\Source\Database\Relationship\RelationshipInterface;

interface RelationshipAttributeInterface
{
    public function create(AbstractEntity $entity) : RelationshipInterface;

    public function getModel() : string;

    public function getProperty(): string;
}
