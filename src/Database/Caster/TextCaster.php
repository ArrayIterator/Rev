<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Caster;

use ArrayIterator\Rev\Source\Database\Caster\Interfaces\CasterInterface;
use ArrayIterator\Rev\Source\Database\Caster\Traits\CasterTrait;

class TextCaster implements CasterInterface
{
    use CasterTrait;

    public function cast($data, array $params = []): string
    {
        return (string) $data;
    }
}
