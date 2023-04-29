<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Caster\Interfaces;

interface CasterInterface
{
    /**
     * @param $data
     * @param array $params
     * @return mixed
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function cast($data, array $params = []);

    public function value($data, array $params = []);
}
