<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Responder\Interfaces;

interface JsonResponderInterface extends ResponderInterface
{
    public function decode(string $data, bool $assoc = true);

    public function encode($data) : string;

    public function format(int $code, $data) : array;
}
