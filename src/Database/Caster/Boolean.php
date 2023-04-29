<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Caster;

use ArrayIterator\Rev\Source\Database\Caster\Interfaces\CasterInterface;
use ArrayIterator\Rev\Source\Database\Caster\Traits\CasterTrait;

class Boolean implements CasterInterface
{
    use CasterTrait;

    public function cast($data, array $params = []): bool
    {
        return (bool) $data;
    }
}
