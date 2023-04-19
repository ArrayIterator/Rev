<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Auth\Google;

use ArrayIterator\XDucator\App\Source\Http\Request\Curl;
use ArrayIterator\XDucator\App\Source\Http\Request\Exceptions\BadRequestException;
use ArrayIterator\XDucator\App\Source\Http\Request\Exceptions\BadResponseException;
use ArrayIterator\XDucator\App\Source\Http\Request\Exceptions\ConnectException;
use InvalidArgumentException;
use UnexpectedValueException;

class OauthClientInfo
{
    const OAUTH2_REVOKE_URI = 'https://oauth2.googleapis.com/revoke';
    const OAUTH2_TOKEN_URI  = 'https://oauth2.googleapis.com/token';
    const OAUTH2_AUTH_URL   = 'https://accounts.google.com/o/oauth2/v2/auth';
    const OPEN_ID_URL       = 'https://openidconnect.googleapis.com/v1/userinfo';
    const BASE_URL          = 'https://accounts.google.com/';

    /**
     * @var array<string, AuthToken>
     */
    private array $storedAuthTokens       = [];

    /**
     * @var array<string ,UserInfo>
     */
    private array $storedInfo         = [];

    /**
     * @var array<string, AccessToken>
     */
    private array $storedAccessToken  = [];

    /**
     * @var array<string, array>
     */
    private array $storedRevoke = [];

    /**
     * @var string
     */
    private string $clientId;

    /**
     * @var string
     */
    private string $clientSecret;

    /**
     * @var string
     */
    private string $redirectUri;

    /**
     * @var string[]
     */
    const SCOPES = [
        'openid',
        'email',
        'profile'
    ];

    public function __construct(
        string $client_id,
        string $client_secret,
        string $redirect_uri
    ) {
        $this->clientId = $client_id;
        $this->clientSecret = $client_secret;
        $this->redirectUri = $redirect_uri;
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * @return string
     */
    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    /**
     * @return array<string,array>
     */
    public function getStoredAuthTokens(): array
    {
        return $this->storedAuthTokens;
    }

    /**
     * @return string
     */
    public function createAuthUrl(): string
    {
        $scope = http_build_query([
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'prompt' => 'consent',
            'access_type' => 'offline',
            'scope' => implode(' ', static::SCOPES),
        ]);
        return sprintf(
            '%s?%s',
            self::OAUTH2_AUTH_URL,
            $scope
        );
    }

    public function isValidReferer(?string $referer = null): bool
    {
        $referer ??= $_SERVER['HTTP_REFERER']??null;
        return is_string($referer) && ($referer === self::BASE_URL
           || str_starts_with(strtolower($referer), self::BASE_URL)
        );
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $options
     *
     * @return Curl
     */
    private function createCurlRequest(
        string $url,
        string $method = 'GET',
        array $options = []
    ): Curl {
        return Curl::createRequest(
            $url,
            $method,
            $options
        );
    }

    /**
     * @param string|AccessToken $accessToken
     *
     * @return UserInfo
     * @throws BadResponseException|BadRequestException|ConnectException
     */
    public function getUserInfo(string|AccessToken $accessToken): UserInfo
    {
        if ($accessToken instanceof AccessToken) {
            $accessToken = $accessToken->getAccessToken();
        }
        if (!is_string($accessToken) || trim($accessToken) === '') {
            throw new InvalidArgumentException(
                'Access token is not valid'
            );
        }
        if (isset($this->storedInfo[$accessToken])) {
            return $this->storedInfo[$accessToken];
        }

        $request = $this->createCurlRequest(
            sprintf(
                '%s?%s',
                self::OPEN_ID_URL,
                http_build_query([
                    'access_token' => $accessToken,
                ])
            ),
            options: [
                'verify' => false
            ]
        );

        $response = (string)$request->execute()->getResponse()->getBody();
        $result = json_decode($response, true);
        if (!is_array($result)) {
            throw new UnexpectedValueException(
                'Can not get user metadata'
            );
        }
        $this->storedInfo[$accessToken] = new UserInfo($accessToken, $result);
        return $this->storedInfo[$accessToken];
    }

    /**
     * @param string|AuthToken|AccessToken $token
     *
     * @return array
     */
    public function revoke(string|AuthToken|AccessToken $token) : array
    {
        $token = is_string($token) ? $token : $token->getRefreshToken();
        if (!is_string($token) || trim($token) === '') {
            throw new InvalidArgumentException(
                'Access token is not valid'
            );
        }

        if (isset($this->storedRevoke[$token])) {
            return $this->storedRevoke[$token];
        }

        $request = $this->createCurlRequest(
            sprintf(
                '%s?%s',
                self::OAUTH2_REVOKE_URI,
                http_build_query(['token' => $token])
            ),
            'POST',
            [
                'verify' => false,
                'formParams' => [
                    'client_id' => $this->getClientId(),
                    'client_secret' => $this->getClientSecret(),
                    'redirect_uri' => $this->getRedirectUri(),
                ]
            ]
        );
        $response = (string)$request->execute()->getResponse()->getBody();
        $result = json_decode($response, true);
        if (!is_array($result)) {
            throw new UnexpectedValueException(
                sprintf('Can not revoke token: %s', $response)
            );
        }
        $this->storedRevoke[$token] = $result;
        return $this->storedRevoke[$token];
    }

    /**
     * @param string|AuthToken $refreshToken
     *
     * @return AccessToken
     * @throws BadResponseException|BadRequestException|ConnectException
     */
    public function accessToken(string|AuthToken $refreshToken): AccessToken
    {
        if ($refreshToken instanceof AuthToken) {
            $refreshToken = $refreshToken->getRefreshToken();
        }
        if (!is_string($refreshToken) || trim($refreshToken) === '') {
            throw new InvalidArgumentException(
                'Refresh token is not valid'
            );
        }

        if (isset($this->storedAccessToken[$refreshToken])) {
            return $this->storedAccessToken[$refreshToken];
        }

        $request = $this->createCurlRequest(
            self::OAUTH2_TOKEN_URI,
            'POST',
            [
                'verify' => false,
                'formParams' => [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => $this->getClientId(),
                    'client_secret' => $this->getClientSecret(),
                    // 'redirect_uri' => $this->getRedirectUri(),
                    'prompt' => 'consent',
                    'access_type' => 'offline',
                ]
            ]
        );
        $response = (string)$request->execute()->getResponse()->getBody();
        $result = json_decode($response, true);
        if (!is_array($result)) {
            throw new UnexpectedValueException(
                sprintf('Could not get access token: %s', $response)
            );
        }

        $this->storedAccessToken[$refreshToken] = new AccessToken($refreshToken, $result);
        return $this->storedAccessToken[$refreshToken];
    }

    /**
     * @param string $authorizationCode
     *
     * @return AuthToken
     * @throws BadResponseException|BadRequestException|ConnectException
     */
    public function authToken(string $authorizationCode): AuthToken
    {
        // prevent recall
        if (isset($this->storedAuthTokens[$authorizationCode])) {
            return $this->storedAuthTokens[$authorizationCode];
        }

        $request = $this->createCurlRequest(
            self::OAUTH2_TOKEN_URI,
            'POST',
            [
                'verify' => false,
                'formParams' => [
                    'grant_type' => 'authorization_code',
                    'code'      => $authorizationCode,
                    'client_id' => $this->getClientId(),
                    'client_secret' => $this->getClientSecret(),
                    'redirect_uri' => $this->getRedirectUri(),
                    'access_type' => 'offline',
                ]
            ]
        );

        $response = (string)$request->execute()->getResponse()->getBody();
        $result = json_decode($response, true);
        if (!is_array($result)) {
            throw new UnexpectedValueException(
                sprintf('Could not get auth token : %s', $response)
            );
        }
        $this->storedAuthTokens[$authorizationCode] = new AuthToken($authorizationCode, $result);
        return $this->storedAuthTokens[$authorizationCode];
    }
}
