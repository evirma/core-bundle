<?php

namespace Evirma\Bundle\CoreBundle\Util;

class ArrayUtil
{
    public static function reindexArray($array, $keyToIndex = 'id')
    {
        $result = [];

        foreach ($array as $item) {
            if (isset($item[$keyToIndex])) {
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

    function arrayMergeRecursiveDistinct(array &$array1, array &$array2)
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset ($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
