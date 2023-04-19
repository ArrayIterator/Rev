<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Auth\Google;

use JsonSerializable;
use Serializable;

abstract class AbstractStorage implements JsonSerializable, Serializable
{
    protected array $data = [];

    protected int $stored_time_gmt;

    public function __construct(array $data)
    {
        $this->stored_time_gmt = time();
        $this->data            = $data;
        if (isset($data['expires_in']) && is_int($data['expires_in'])) {
            $this->data['expires_at_gmt'] = $this->stored_time_gmt + $data['expires_in'];
        }
    }

    public function isExpired() : ?bool
    {
        $expired_at = $this->get('expires_at_gmt');
        if (!is_int($expired_at)) {
            return null;
        }

        return $expired_at < time();
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function isValid() : bool
    {
        return true;
    }

    protected function isValidJWT($data): bool
    {
        return is_string($data) && preg_match('~^[\w-]+\.[\w-]+\.[\w-]+$~', $data);
    }

    /**
     * @return int
     */
    public function getStoredTimeGmt(): int
    {
        return $this->stored_time_gmt;
    }

    /**
     * @param float|int|string $name
     *
     * @return mixed $name
     */
    public function get(float|int|string $name) : mixed
    {
        return $this->data[$name]??null;
    }

    public function toArray() : array
    {
        return get_object_vars($this);
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function serialize(): ?string
    {
        return serialize($this->toArray());
    }

    /** @noinspection PhpMissingReturnTypeInspection */
    public function unserialize($data)
    {
        $data = @unserialize($data);
        if (is_array($data)) {
            $this->__unserialize($data);
        }
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __set(string $name, $value): void
    {
        // no set
    }

    public function __get(string $name)
    {
        return $this->get($name);
    }
}
