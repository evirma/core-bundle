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
}
