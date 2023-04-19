<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Auth\Google;

class AuthToken extends AbstractStorage
{
    protected string $authorizationCode;

    public function __construct(string $authorizationCode, array $data)
    {
        unset($data['expires_at_gmt']);
        parent::__construct($data);
        $this->authorizationCode = $authorizationCode;
    }

    public function isValid(): bool
    {
        return $this->getExpiresIn() > 0
               && is_string($this->getRefreshToken())
               && is_string($this->getScope())
               && is_string($this->getTokenType())
               && is_string($this->getAccessToken())
               && preg_match('~^[\w-]+\.[\w-]+$~', $this->getAccessToken())
               && preg_match('~^[\w-/]+[\w-]+$~', $this->getRefreshToken())
               && $this->isValidJWT($this->getIdToken());
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

    public function getRefreshToken()
    {
        return $this->get('refresh_token');
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
