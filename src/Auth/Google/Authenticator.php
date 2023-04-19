<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Auth\Google;

use Throwable;

class Authenticator
{
    protected int $time;

    public function __construct()
    {
        $this->time = time();
    }

    /**
     * @param int $time
     * @param int $offset
     *
     * @return float|int
     */
    public function getTimeSlice(int $time, int $offset = 0): float|int
    {
        return floor($time / 30) + $offset;
    }

    public function isEqual(string $string1, string $string2): bool
    {
        return substr_count($string1 ^ $string2, "\0") * 2 === strlen($string1 . $string2);
    }

    /**
     * @param string $secret
     * @param string $code
     * @param ?int $time
     *
     * @return bool
     */
    public function authenticate(string $secret, string $code, ?int $time = null): bool
    {
        $time    ??= $this->time;
        $window  = 1;
        $correct = false;
        for ($i = -$window; $i <= $window; $i++) {
            $timeSlice = $this->getTimeSlice($time, $i);
            if ($this->isEqual($this->calculateCode($secret, $timeSlice, strlen($code)), $code)) {
                $correct = true;
                break;
            }
        }

        return $correct;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    public function randomString(
        int $length
    ): string {
        $keyspace = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $str = '';
        for ($i = 0; $i < $length; ++$i) {
            $keyspace = str_shuffle($keyspace);
            $str .= $keyspace[$i];
        }
        return $str;
    }

    public function generateRandomCode(int $length = 16, string|int|float $prefix = ''): string
    {
        try {
            $random = random_bytes($length);
        } catch (Throwable) {
            $random = '';
            for ($i = 0; $i < $length; ++$i) {
                $random .= chr(mt_rand(0, 256));
            }
        }

        return substr(rtrim(Converter::base32Encode($prefix . $random), '='), 0, $length);
    }

    /**
     * @param string $secret
     * @param int|float|null $timeSlice
     * @param int $codeLength
     *
     * @return string
     */
    public function calculateCode(
        string $secret,
        int|float|null $timeSlice = null,
        int $codeLength = 6
    ): string {
        $timeSlice ??= $this->getTimeSlice($this->time);
        $timeSlice = pack("N", $timeSlice);
        $timeSlice = str_pad($timeSlice, 8, chr(0), STR_PAD_LEFT);
        $hash = hash_hmac("sha1", $timeSlice, Converter::base32Decode($secret), true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $result = substr($hash, $offset, 4);
        $value = unpack('N', $result)[1];
        $value = $value & 0x7FFFFFFF;
        $modulo = pow(10, $codeLength);
        return str_pad((string)($value % $modulo), $codeLength, '0', STR_PAD_LEFT);
    }
}
