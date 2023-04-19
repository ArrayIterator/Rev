<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Auth\Google;

class UserInfo extends AbstractStorage
{
    protected string $accessToken;

    public function __construct(string $accessToken, array $data)
    {
        parent::__construct($data);
        $this->accessToken = $accessToken;
    }

    public function isValid() : bool
    {
        return is_int($this->getId())
            && ($this->getEmail() === null || is_string($this->getEmail()))
            && ($this->getLocale() === null || is_string($this->getLocale()))
            && ($this->getGivenName() === null || is_string($this->getGivenName()))
            && ($this->getName() === null || is_string($this->getName()));
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @return ?int
     */
    public function getId() : ?int
    {
        $sub = $this->get('sub');
        return is_numeric($sub) ? (int) $sub : null;
    }

    public function getEmail()
    {
        return $this->get('email');
    }

    public function getLocale()
    {
        return $this->get('locale');
    }

    public function isEmailVerified(): ?bool
    {
        $data = $this->get('email_verified');
        if ($data === null) {
            return null;
        }
        return (bool) $data;
    }

    public function getGivenName()
    {
        return $this->get('given_name');
    }

    /**
     * aliases of @function getGivenName()
     */
    public function getFirstName()
    {
        return $this->getGivenName();
    }

    public function getPicture()
    {
        return $this->get('picture');
    }

    public function getName()
    {
        return $this->get('name');
    }
}
