<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Auth\Google;

class AccessToken extends AbstractStorage
{
    protected string $refreshToken;

    public function __construct(string $refreshToken, array $data)
    {
        unset($data['expires_at_gmt']);
        parent::__construct($data);
        $this->refreshToken = $refreshToken;
    }

    public function isValid(): bool
    {
        return $this->getExpiresIn() > 0
            && is_string($this->getScope())
            && is_string($this->getTokenType())
            && is_string($this->getAccessToken())
            && preg_match('~^[\w-]+\.[\w-]+$~', $this->getAccessToken())
            && $this->isValidJWT($this->getIdToken());
    }

    /**
     * @return string
     */
    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function getAccessToken()
    {
        return $this->get('access_token');
    }

    public function getExpiresIn() : ?int
    {
        $expires = $this->get('expires_in');
        return is_int($expires) ? $expires : null;
    }

    public function getScope()
    {
        return $this->get('scope');
    }

    public function getTokenType()
    {
        return $this->get('token_type');
    }

    public function getIdToken()
    {
        return $this->get('id_token');
    }
}
