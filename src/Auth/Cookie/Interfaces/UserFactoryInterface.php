<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Auth\Cookie\Interfaces;

interface UserFactoryInterface
{
    /**
     * @param int $user_id
     *
     * @return ?UserBasedInterface
     */
    public function findById(int $user_id) : ?UserBasedInterface;

    /**
     * @param string $email
     *
     * @return ?UserBasedInterface
     */
    public function findByEmail(string $email) : ?UserBasedInterface;

    /**
     * @param string $username
     *
     * @return ?UserBasedInterface
     */
    public function findByUsername(string $username) : ?UserBasedInterface;

    /**
     * @param UserBasedInterface $userBasedEntity
     *
     * @return bool
     */
    public function isValid(UserBasedInterface $userBasedEntity) : bool;

    /**
     * @return static
     */
    public static function createInstance() : static;
}
