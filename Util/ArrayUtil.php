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
}
