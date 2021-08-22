<?php

namespace Evirma\Bundle\CoreBundle\Util;

use ArrayObject;

class ArrayUtil
{
    public static function reindexArray($array, $keyToIndex = 'id')
    {
        $result = [];

        foreach ($array as $item) {
            if (is_scalar($item)) {
                $result[(int)$item] = (int)$item;
            } elseif (isset($item[$keyToIndex])) {
                $result[$item[$keyToIndex]] = $item;
            }
        }

        return $result;
    }

    public static function stringify($array)
    {
        foreach ($array as &$item) {
            if (is_numeric($item)) {
                $item = (string)$item;
                continue;
            }

            if (is_object($item) || is_array($item)) {
                $item = ArrayUtil::stringify($item);
            }
        } // recurse!

        return $array;
    }

    public static function arrayMergeRecursiveDistinct(array $array1, array $array2)
    {
        $merged = $array1;
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset ($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = ArrayUtil::arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    public static function hash(array $array)
    {
        array_multisort($array);

        return md5(serialize($array));
    }

    public static function hasKeys($keys, array|ArrayObject $array)
    {
        if (empty($array)) {
            return false;
        }

        if (!is_array($keys)) {
            $keys = [$keys];
        }

        foreach ($keys as $key) {
            if (isset($array[$key]) || array_key_exists($key, $array)) {
                continue;
            } else {
                return false;
            }
        }

        return true;
    }
}
