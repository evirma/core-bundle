<?php

namespace Evirma\Bundle\CoreBundle\Util;

class JsonUtil
{
    public static function encode($data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public static function encodeStringify($data)
    {
        return json_encode(ArrayUtil::stringify($data), JSON_UNESCAPED_UNICODE);
    }

    public static function decode($data)
    {
        return json_decode($data, true);
    }
}
