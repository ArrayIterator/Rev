<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Auth\Cookie\Interfaces;

interface UserBasedInterface
{
    public function getId() : ?int;

    public function getUsername() : ?string;

    public function isDeleted() : bool;
}
