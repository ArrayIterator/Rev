<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Responder\Interfaces;

interface HtmlResponderInterface extends ResponderInterface
{
    public function format(int $code, $data) : string;
}
