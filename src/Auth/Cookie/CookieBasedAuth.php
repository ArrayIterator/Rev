<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Auth\Cookie;

use ArrayIterator\Rev\Source\Auth\Cookie\Interfaces\UserBasedInterface;
use ArrayIterator\Rev\Source\Auth\Cookie\Interfaces\UserFactoryInterface;
use ArrayIterator\Rev\Source\Events\EventsManagerInterface;
use ArrayIterator\Rev\Source\Events\Manager;
use ArrayIterator\Rev\Source\Traits\ServerRequestFactoryTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class CookieBasedAuth
{
    use ServerRequestFactoryTrait;

    /**
     * @var string
     */
    private string $cookieName;

    /**
     * False if not login, or null has not been set
     *
     * @var ?UserBasedInterface|false
     */
    protected UserBasedInterface|false|null $user = null;

    /**
     * @var string|false|null
     */
    protected string|false|null $cookieValue = null;

    /**
     * @var ?array{
     *     hash: string,
     *     time: int,
     *     username_hash: string,
     *     random: string,
     *     user_agent_hash: string
     * }
     */
    private ?array $cookieDetails = null;

    /**
     * @var array<string,false|array>
     */
    private array $hash_records = [];

    /**
     * @var bool Skip User Agent Check
     */
    private bool $skipUserAgentCheck = false;

    private ?string $userAgentHash = null;

    protected ServerRequestInterface $request;

    protected EventsManagerInterface $eventsManager;

    protected ?ContainerInterface $container = null;

    /**
     * @param string $cookieName
     * @param UserFactoryInterface $userFactory
     * @param string $secretKey
     * @param ServerRequestInterface|null $request
     * @param EventsManagerInterface|null $eventsManager
     */
    public function __construct(
        string $cookieName,
        protected UserFactoryInterface $userFactory,
        private readonly string $secretKey,
        ?ServerRequestInterface $request = null,
        ?EventsManagerInterface $eventsManager = null,
        ?ContainerInterface $container = null
    ) {
        if ($container) {
            $this->setContainer($container);
        }
        $this->setEventsManager($eventsManager??Manager::getEventsManager());
        $this->setRequest($request??$this->getServerRequestFromGlobals());
        $this->cookieName = $cookieName;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @param ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * @return EventsManagerInterface
     */
    public function getEventsManager(): EventsManagerInterface
    {
        return $this->eventsManager;
    }

    /**
     * @param EventsManagerInterface $eventsManager
     */
    public function setEventsManager(EventsManagerInterface $eventsManager): void
    {
        $this->eventsManager = $eventsManager;
    }

    /**
     * @return ?ContainerInterface
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @return string
     */
    public function getUserAgentHash(): string
    {
        $this->userAgentHash ??= sha1(implode('|', $this->getBrowserNameBased()));
        return $this->userAgentHash;
    }

    /**
     * @return bool
     */
    public function isSkipUserAgentCheck(): bool
    {
        return $this->skipUserAgentCheck;
    }

    /**
     * @param bool $skipUserAgentCheck
     */
    public function setSkipUserAgentCheck(bool $skipUserAgentCheck): void
    {
        $this->skipUserAgentCheck = $skipUserAgentCheck;
    }

    /**
     * @return array
     */
    public function getHashRecords(): array
    {
        return $this->hash_records;
    }

    /**
     * @return UserFactoryInterface
     */
    public function getUserFactory(): UserFactoryInterface
    {
        return $this->userFactory;
    }

    public function getUserById(int $id): ?UserBasedInterface
    {
        return $this->userFactory->findById($id);
    }

    /**
     * @return ?string
     */
    public function getCookieValue(): ?string
    {
        if ($this->cookieValue === null) {
            $cookie = $this->getRequest()->getCookieParams()[$this->getCookieName()]??null;
            $this->cookieValue = is_string($cookie) ? $cookie : false;
        }

        return $this->cookieValue === false ? null : $this->cookieValue;
    }

    /**
     * @return string
     */
    public function getCookieName(): string
    {
        return $this->cookieName;
    }

    /**
     * @return ?array{
     *     hash: string,
     *     time: int,
     *     username_hash: string,
     *     random: string,
     *     user_agent_hash: string
     * }
     */
    public function getCookieDetails(): ?array
    {
        return $this->cookieDetails;
    }

    /**
     * @param string $cookieName
     */
    public function setCookieName(string $cookieName): void
    {
        if ($this->cookieName !== $cookieName) {
            $this->cookieValue = null;
            $this->user = null;
        }
        $this->cookieName = $cookieName;
    }

    /**
     * @return ?UserBasedInterface
     */
    public function getDirectUser(): ?UserBasedInterface
    {
        return $this->user ?: null;
    }

    public function getUser(): ?UserBasedInterface
    {
        if ($this->user !== null) {
            return $this->user ?: null;
        }

        return $this->isLogin() ? ($this->user ?: null) : null;
    }

    public function generateCookieValue(UserBasedInterface $UserBasedEntityModel): ?string
    {
        $cookieValue = null;
        try {
            $userId = $UserBasedEntityModel->getId();
            $username = $UserBasedEntityModel->getUsername();
            if (!is_int($userId)
                || !is_string($username)
                || trim($username) === ''
            ) {
                return null;
            }

            $userIdHex = dechex($userId);
            $random = substr(sha1(microtime() . microtime()), 0, 30);
            $timeHex = dechex(time());
            $usernameHex = hash_hmac(
                'md5',
                strtolower(trim($username)),
                $this->secretKey . $random
            );
            $hash = hash_hmac(
                'sha1',
                "$usernameHex|$random|$userIdHex|$timeHex",
                $this->secretKey
            );

            // add additional validation
            $agent = $this->getRequest()->getHeaderLine('User-Agent') ?: '';
            $agentHash = sha1(implode('|', $this->getBrowserNameBased($agent)));
            $cookieValue = "$hash$timeHex$usernameHex$userIdHex$random$agentHash";
        } finally {
            return $cookieValue;
        }
    }

    /**
     * For note only
     *
     * @return array{system:string,browser:string}
     */
    protected function getBrowserNameBased(): array
    {
        $ua = $this->getRequest()->getHeaderLine('User-Agent');
        $result = [];
        preg_match('~^[^(]+\(([^;\s)]+);~', $ua, $match);
        $result['system'] = strtolower(trim($match[1] ?? 'unknown'));
        $regex = [
            'edge' => '~\s+Edge/~i',
            'msie' => '~\s+MSIE\s+[1-9]~i',
            'firefox' => '~\s+Firefox/~i',
            'opera' => '~\s+Opera/~i',
            'chrome' => '~\s+Chrome/~i',
            'safari' => '~AppleWebKit.*\s+Safari/[0-9\.]+\s*$~i',
            'webkit' => '~\s+Webkit/~i',
        ];
        foreach ($regex as $name => $re) {
            if (preg_match($re, $ua)) {
                $result['browser'] = $name;
                break;
            }
        }
        if (!isset($result['browser'])) {
            $result['browser'] = 'unknown';
        }

        return $result;
    }

    /**
     * @param string $cookieValue
     * @param bool $skipUserAgent
     *
     * @return ?array{
     *     hash: string,
     *     time: int,
     *     username_hash: string,
     *     random: string,
     *     user_agent_hash: string
     * }
     */
    public function validateCookieValue(string $cookieValue, bool $skipUserAgent = false): ?array
    {
        $hashKey = sha1($cookieValue);
        if (isset($this->hash_records[$hashKey])) {
            $result =  $this->hash_records[$hashKey] ?: null;
            if (!$result) {
                return null;
            }
            if ($skipUserAgent) {
                return $result;
            }
            return hash_equals($result['user_agent_hash']??'', $this->getUserAgentHash())
                ? $result
                : null;
        }

        $this->hash_records[$hashKey] = false;
        preg_match(
            '~^([a-f0-9]{40})  # hash
            ([a-f0-9]{8})  # timeHex
            ([a-f0-9]{32}) # usernameHex
            ([a-f0-9]+)    # userIdHex
            ([a-f0-9]{30}) # random
            ([a-f0-9]{40}) # UAHash
        $~x',
            $cookieValue,
            $match
        );
        if (empty($match)) {
            return null;
        }

        $hash = $match[1];
        $timeHex = $match[2];
        $usernameHex = $match[3];
        $userIdHex = $match[4];
        $random = $match[5];
        $UAHash = $match[6];
        $time = hexdec($timeHex);
        $fiveYearsAgo = strtotime('-5 Years');

        /**
         * Validate sign
         */
        // check if time less than 5 years ago
        // or the time greater than current time
        if ($fiveYearsAgo >= $time || time() < $time) {
            return null;
        }

        $newHash = hash_hmac(
            'sha1',
            "$usernameHex|$random|$userIdHex|$timeHex",
            $this->secretKey
        );

        if (!hash_equals($hash, $newHash)) {
            return null;
        }

        // validate user agent
        $userId = hexdec($userIdHex);
        $this->hash_records[$hashKey] = [
            'hash' => $hash,
            'time' => $time,
            'username_hash' => $usernameHex,
            'user_id' => $userId,
            'random' => $random,
            'user_agent_hash' => $UAHash,
        ];
        if (!$skipUserAgent && !hash_equals($this->getUserAgentHash(), $UAHash)) {
            return null;
        }
        return $this->hash_records[$hashKey];
    }

    /**
     * @param string $cookieValue
     * @param $cookieDetails
     *
     * @return bool|UserBasedInterface|null
     *      null if user not found, else if invalid otherwise @uses UserBasedInterface
     */
    public function getUserByCookieValue(
        string $cookieValue,
        &$cookieDetails = null
    ): bool|UserBasedInterface|null {
        try {
            $user = false;
            $data = $this->validateCookieValue(
                $cookieValue,
                $this->isSkipUserAgentCheck()
            );
            if (!$data) {
                return false;
            }
            $user = $this->userFactory->findById($data['user_id']);
            if (!$user) {
                $user = null;
                return null;
            }
            $random = $data['random'];
            $usernameHex = $data['username_hash'];
            $username = $user->getUsername();
            if (!is_string($username) || empty($username)) {
                $user = null;

                return null;
            }
            $usernameMd5 = hash_hmac(
                'md5',
                strtolower(trim($username)),
                $this->secretKey . $random
            );
            if (!hash_equals($usernameHex, $usernameMd5)) {
                $user = null;

                return false;
            }
            $cookieDetails = $data;

            return $user;
        } finally {
            return $user;
        }
    }

    public function isLogin(): bool
    {
        if ($this->user === null) {
            $this->user = false;
            $cookie = $this->getCookieValue();
            if (is_string($cookie)) {
                $user = $this->getUserByCookieValue($cookie, $cookieDetails);
                $this->user = $user && !$user->isDeleted() && $this->userFactory->isValid($user)
                    ? $user
                    : false;
                if (is_array($cookieDetails)) {
                    $this->cookieDetails = $cookieDetails;
                }
            }
        }

        return $this->user instanceof UserBasedInterface;
    }

    public function __destruct()
    {
        $this->hash_records = [];
    }
}
