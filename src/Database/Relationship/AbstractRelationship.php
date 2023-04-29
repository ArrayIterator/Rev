<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Relationship;

use ArrayIterator\Rev\Source\Database\Mapping\AbstractEntity;
use ArrayIterator\Rev\Source\Database\Mapping\AbstractModel;
use ArrayIterator\Rev\Source\Database\Relationship\Attributes\RelationshipAttributeInterface;

abstract class AbstractRelationship implements RelationshipInterface
{
    protected readonly AbstractEntity $entity;

    protected readonly RelationshipAttributeInterface $attribute;

    protected ?AbstractModel $objectModel = null;

    public function __construct(AbstractEntity $entity, RelationshipAttributeInterface $relationshipAttribute)
    {
        $this->entity = $entity;
        $this->attribute = $relationshipAttribute;
        $this->init();
    }

    protected function init()
    {
        // override
    }

    /**
     * @return AbstractEntity
     */
    public function getEntity(): AbstractEntity
    {
        return $this->entity;
    }

    /**
     * @return RelationshipAttributeInterface
     */
    public function getAttribute(): RelationshipAttributeInterface
    {
        return $this->attribute;
    }

    /**
     * @return AbstractModel
     */
    protected function getObjectModel() : AbstractModel
    {
        if (!$this->objectModel) {
            $model = $this->attribute->getModel();
            $this->objectModel = new $model(
                $this->entity->getModel()->getConnection()
            );
        }
        return $this->objectModel;
    }
}
