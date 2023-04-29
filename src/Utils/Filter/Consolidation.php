<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Utils\Filter;

use Closure;
use Exception;
use ReflectionFunction;
use ReflectionObject;
use RuntimeException;
use function explode;
use function str_contains;
use function strlen;

class Consolidation
{
    public static function mapDeep($value, callable $callback)
    {
        if (is_array($value)) {
            foreach ($value as $index => $item) {
                $value[$index] = self::mapDeep($item, $callback);
            }
        } elseif (is_object($value)) {
            $object_vars = get_object_vars($value);
            foreach ($object_vars as $property_name => $property_value) {
                $value->$property_name = self::mapDeep($property_value, $callback);
            }
        } else {
            $value = call_user_func($callback, $value);
        }

        return $value;
    }

    /**
     * @param $data
     * @param string|null $prefix
     * @param string|null $sep
     * @param string $key
     * @param bool $urlEncode
     * @return string
     */
    public static function buildQuery(
        $data,
        string $prefix = null,
        string $sep = null,
        string $key = '',
        bool $urlEncode = true
    ): string {
        $ret = [];

        foreach ((array)$data as $k => $v) {
            if ($urlEncode) {
                $k = urlencode((string) $k);
            }
            if (is_int($k) && null != $prefix) {
                $k = $prefix . $k;
            }
            if (!empty($key)) {
                $k = $key . '%5B' . $k . '%5D';
            }
            if (null === $v) {
                continue;
            } elseif (false === $v) {
                $v = '0';
            }

            if (is_array($v) || is_object($v)) {
                $ret[] = self::buildQuery($v, '', $sep, $k, $urlEncode);
            } elseif ($urlEncode) {
                $ret[] = $k . '=' . urlencode((string) $v);
            } else {
                $ret[] = $k . '=' . $v;
            }
        }

        if (null === $sep) {
            $sep = ini_get('arg_separator.output');
        }

        return implode($sep, $ret);
    }

    /**
     * @param mixed ...$args
     * @return string
     */
    public static function addQueryArgs(...$args): string
    {
        if (!isset($args[0])) {
            return '';
        }
        $uri_ = $args[0];

        if (is_array($uri_)) {
            if (count($args) < 2 || false === $args[1]) {
                $uri = $_SERVER['REQUEST_URI']; //$_SERVER['REQUEST_URI'];
            } else {
                $uri = $args[1];
            }
        } else {
            if (count($args) < 3 || false === $args[2]) {
                if (is_string($args[0]) && preg_match('#^([^:]+://|/)#i', $args[0])) {
                    $uri = $args[0];
                    unset($args[0]);
                    $args = array_values($args);
                } elseif (is_string($args[1]) && preg_match('#^([^:]+://|/)#i', $args[1])) {
                    $uri = $args[1];
                    unset($args[1]);
                    $args = array_values($args);
                } else {
                    $uri = $_SERVER['REQUEST_URI']; // ['REQUEST_URI'];
                }
            } else {
                $uri = $args[2];
            }
        }

        $frag = strstr($uri, '#');
        if ($frag) {
            $uri = substr($uri, 0, -strlen($frag));
        } else {
            $frag = '';
        }

        if (0 === stripos($uri, 'http://')) {
            $protocol = 'http://';
            $uri = substr($uri, 7);
        } elseif (0 === stripos($uri, 'https://')) {
            $protocol = 'https://';
            $uri = substr($uri, 8);
        } else {
            $protocol = '';
        }

        if (str_contains($uri, '?')) {
            [$base, $query] = explode('?', $uri, 2);
            $base .= '?';
        } elseif ($protocol || !str_contains($uri, '=')) {
            $base = $uri . '?';
            $query = '';
        } else {
            $base = '';
            $query = $uri;
        }

        parse_str($query, $qs);

        $qs = self::mapDeep($qs, 'urldecode');
        // $qs = self::mapDeep($qs, 'urlencode');
        if (is_array($args[0])) {
            foreach ($args[0] as $k => $v) {
                $qs[$k] = $v;
            }
        } elseif (isset($args[1])) {
            $qs[$args[0]] = $args[1];
        }

        foreach ($qs as $k => $v) {
            if (false === $v) {
                unset($qs[$k]);
            }
        }

        $ret = self::buildQuery($qs);
        $ret = trim($ret, '?');
        $ret = preg_replace('#=(&|$)#', '$1', $ret);
        $ret = $protocol . $base . $ret . $frag;

        return rtrim($ret, '?');
    }

    /**
     * Removes an item or items from a query string.
     *
     * @param array|string $key Query key or keys to remove.
     * @param bool|string $query Optional. When false uses the current URL. Default false.
     *
     * @return string New URL query string.
     */
    public static function removeQueryArg(array|string $key, bool|string $query = false): string
    {
        if (is_array($key)) {
            // Removing multiple keys.
            foreach ($key as $k) {
                $query = self::addQueryArgs($k, false, $query);
            }

            return $query;
        }
        return self::addQueryArgs($key, false, $query);
    }

    /**
     * @param callable $callback
     * @param $errNo
     * @param $errStr
     * @param $errFile
     * @param $errLine
     * @param $errContext
     *
     * @return mixed
     */
    public static function callbackReduceError(
        callable $callback,
        &$errNo = null,
        &$errStr = null,
        &$errFile = null,
        &$errLine = null,
        &$errContext = null
    ): mixed {
        set_error_handler(static function (
            $no,
            $str,
            $file,
            $line,
            $c = null
        ) use (
            &$errNo,
            &$errStr,
            &$errFile,
            &$errLine,
            &$errContext
        ) {
            $errNo = $no;
            $errStr = $str;
            $errFile = $file;
            $errLine = $line;
            $errContext = $c;
        });
        $result = $callback();
        restore_error_handler();

        return $result;
    }

    /**
     * @param string|object $className
     *
     * @return string
     */
    public static function getNameSpace(string|object $className): string
    {
        if (is_object($className)) {
            return (new ReflectionObject($className))->getNamespaceName();
        }
        $className = ltrim($className, '\\');

        return preg_replace('~^(.+)?\\\[^\\\]+$~', '$1', $className);
    }

    private static array $classNameCached = [];

    /**
     * @param string|object $fullClassName
     *
     * @return string
     */
    public static function getBaseClassName(string|object $fullClassName): string
    {
        if (is_object($fullClassName)) {
            $className = strtolower(get_class($fullClassName));
            if (!isset(self::$classNameCached[$className])) {
                self::$classNameCached[$className] = (new ReflectionObject($fullClassName))->getShortName();
            }

            return self::$classNameCached[$className];
        }
        $fullClassName = ltrim($fullClassName, '\\');
        $className = strtolower($fullClassName);
        if (!isset(self::$classNameCached[$className])) {
            self::$classNameCached[$className] = preg_replace('~^(?:.+\\\)?([^\\\]+)$~', '$1', $fullClassName);
        }

        return self::$classNameCached[$className];
    }

    /**
     * Doing require file with no $this object
     *
     * @param string $file
     * @param bool $once
     * @param array $arguments
     * @param null $found
     *
     * @return mixed
     */
    public static function requireNull(
        string $file,
        bool $once = false,
        array $arguments = [],
        &$found = null
    ): mixed {
        $found = is_file($file) && is_readable($file);

        return $found
            ? (static fn($arguments) => $once ? require_once $file : require $file)->bindTo(null)($arguments)
            : false;
    }

    /**
     * Doing include file with no $this object
     *
     * @param string $file
     * @param bool $once
     * @param $found
     *
     * @return mixed
     */
    public static function includeNull(string $file, bool $once = false, &$found = null): mixed
    {
        $found = is_file($file) && is_readable($file);

        return $found
            ? (static fn() => $once ? include_once $file : include $file)->bindTo(null)()
            : false;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public static function convertNotationValue(mixed $value): mixed
    {
        $annotator = [
            'true' => true,
            'TRUE' => true,
            'false' => false,
            'FALSE' => false,
            'NULL' => null,
            'null' => null,
        ];
        if (is_string($value)) {
            if (is_numeric($value)) {
                return str_contains($value, '.') ? (float)$value : (int)$value;
            }
            return array_key_exists($value, $annotator) ? $annotator[$value] : $value;
        }

        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = self::convertNotationValue($val);
            }
        }

        return $value;
    }

    /**
     * Object binding suitable to call private method
     *
     * @param Closure $closure
     * @param object $object
     *
     * @return Closure
     * @throws Exception|RuntimeException
     */
    public static function objectBinding(
        Closure $closure,
        object $object
    ): Closure {
        $reflectedClosure = new ReflectionFunction($closure);
        $isBindable = (
            ! $reflectedClosure->isStatic()
            || ! $reflectedClosure->getClosureScopeClass()
            || $reflectedClosure->getClosureThis() !== null
        );
        if (!$isBindable) {
            throw new RuntimeException(
                'Cannot bind an instance to a static closure.'
            );
        }

        return $closure->bindTo($object, get_class($object));
    }

    /**
     * Call object binding
     *
     * @param Closure $closure
     * @param object $object
     * @param ...$args
     *
     * @return mixed
     * @throws Exception
     */
    public static function callObjectBinding(Closure $closure, object $object, ...$args): mixed
    {
        return call_user_func_array(self::objectBinding($closure, $object), $args);
    }

    /**
     * @param array $array
     * @param string $delimiter
     *
     * @return array
     */
    public static function notationToArray(array $array, string $delimiter = '.'): array
    {
        $result = [];
        foreach ($array as $notation => $value) {
            if (!is_string($notation)) {
                $result[$notation] = $value;
                continue;
            }
            $keys = explode($delimiter, $notation);
            $keys = array_reverse($keys);
            $lastVal = $value;
            foreach ($keys as $key) {
                $lastVal = [$key => $lastVal];
            }
            // merge result
            $result = array_merge_recursive($result, $lastVal);
        }

        return self::convertNotationValue($result);
    }
}
