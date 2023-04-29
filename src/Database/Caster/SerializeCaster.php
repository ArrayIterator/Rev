<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Caster;

use ArrayIterator\Rev\Source\Database\Caster\Interfaces\CasterInterface;
use ArrayIterator\Rev\Source\Database\Caster\Traits\CasterTrait;
use ArrayIterator\Rev\Source\Utils\Filter\DataType;

class SerializeCaster implements CasterInterface
{
    use CasterTrait;

    public function cast($data, array $params = [])
    {
        return DataType::shouldUnSerialize($data);
    }

    public function value($data, array $params = [])
    {
        return DataType::shouldSerialize($data);
    }
}
