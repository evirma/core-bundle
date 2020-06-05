<?php

namespace Evirma\Bundle\CoreBundle\Domain\ValueObject;

use Evirma\Bundle\CoreBundle\Serializer\SerializerHandler;
use Evirma\Bundle\CoreBundle\Traits\SetGetExtraTrait;
use JsonSerializable;

abstract class ValueObject implements JsonSerializable
{
    use SetGetExtraTrait;

    public static function factory($data)
    {
        if (is_null($data)) {
            return null;
        }

        $class = static::class;
        if ($data instanceof $class) {
            return $data;
        } elseif (is_array($data)) {
            $result = SerializerHandler::denormalizeArrayToObject($data, static::class);

            return ($result instanceof $class) ? $result : null;
        }

        return null;
    }

    public function toArray()
    {
        return SerializerHandler::objectToArray($this);
    }

    public function toJson()
    {
        return SerializerHandler::objectToJson($this);
    }

    public function fromJson(string $json)
    {
        return self::factory(json_decode($json));
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
