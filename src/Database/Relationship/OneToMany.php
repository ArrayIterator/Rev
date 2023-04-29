<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Relationship;

use ArrayIterator\Rev\Source\Database\Mapping\AbstractEntity;
use ArrayIterator\Rev\Source\Database\Mapping\ResultSet;

class OneToMany extends AbstractRelationship
{
    protected ?ResultSet $resultSet = null;

    protected function getResultSet(): ResultSet
    {
        if ($this->resultSet) {
            return $this->resultSet;
        }

        /**
         * @var Attributes\OneToMany $attr
         */
        $attr = $this->attribute;
        $this->resultSet = $this
            ->getObjectModel()
            ->setFirstResult(0)
            ->setMaxResults(null)
            ->where([
                $attr->foreignKey => $this->entity->get($attr->localKey)
            ])->getResult();
        return $this->resultSet;
    }

    public function first(): ?AbstractEntity
    {
        return $this->getResultSet()->first();
    }

    public function all(): array
    {
        return $this->getResultSet()->fetchAll();
    }
}
