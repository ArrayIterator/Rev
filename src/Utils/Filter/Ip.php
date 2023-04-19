<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Utils\Filter;

class Ip
{
    const IP4 = 4;
    const IP6 = 6;

    /**
     * Validates an IPv4 address
     *
     * @param string $value
     * @return bool
     */
    public static function isValidIpv4(string $value): bool
    {
        if (preg_match('/^([01]{8}\.){3}[01]{8}\z/i', $value)) {
            // binary format  00000000.00000000.00000000.00000000
            $value = bindec(\substr($value, 0, 8))
                     . '.'
                     . bindec(\substr($value, 9, 8))
                     . '.'
                     . bindec(\substr($value, 18, 8))
                     . '.'
                     . bindec(\substr($value, 27, 8));
        } elseif (preg_match('/^([0-9]{3}\.){3}[0-9]{3}\z/i', $value)) {
            // octet format 777.777.777.777
            $value = (int) substr($value, 0, 3) . '.' . (int) substr($value, 4, 3) . '.'
                     . (int) substr($value, 8, 3) . '.' . (int) substr($value, 12, 3);
        } elseif (preg_match('/^([0-9a-f]{2}\.){3}[0-9a-f]{2}\z/i', $value)) {
            // hex format ff.ff.ff.ff
            $value = hexdec(\substr($value, 0, 2)) . '.' . hexdec(\substr($value, 3, 2)) . '.'
                     . hexdec(\substr($value, 6, 2)) . '.' . hexdec(\substr($value, 9, 2));
        }

        $ip2long = ip2long($value);
        if ($ip2long === false) {
            return false;
        }

        return $value == long2ip($ip2long);
    }

    /**
     * Validates an IPv6 address
     *
     * @param  string $value Value to check against
     * @return bool True when $value is a valid ipv6 address
     *                 False otherwise
     */
    public static function isValidIpv6(string $value): bool
    {
        if (\strlen($value) < 3) {
            return $value == '::';
        }

        if (\str_contains($value, '.')) {
            $last_colon = strrpos($value, ':');
            if (! ($last_colon && self::isValidIpv4(\substr($value, $last_colon + 1)))) {
                return false;
            }

            $value = substr($value, 0, $last_colon) . ':0:0';
        }

        if (\str_contains($value, '::') === false) {
            return (bool) preg_match('/\A(?:[a-f0-9]{1,4}:){7}[a-f0-9]{1,4}\z/i', $value);
        }

        $colonCount = substr_count($value, ':');
        if ($colonCount < 8) {
            return (bool) preg_match('/\A(?::|(?:[a-f0-9]{1,4}:)+):(?:(?:[a-f0-9]{1,4}:)*[a-f0-9]{1,4})?\z/i', $value);
        }

        // special case with ending or starting double colon
        if ($colonCount == 8) {
            return (bool) preg_match('/\A(?:::)?(?:[a-f0-9]{1,4}:){6}[a-f0-9]{1,4}(?:::)?\z/i', $value);
        }

        return false;
    }

    /**
     * @param string|mixed $ip
     *
     * @return int|false
     */
    public static function version(mixed $ip) : int|false
    {
        return !is_string($ip) ? false : (
            self::isValidIpv4($ip)
            ? self::IP4
            : (self::isValidIpv6($ip) ? self::IP6 : false)
        );
    }
}
