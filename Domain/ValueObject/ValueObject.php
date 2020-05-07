<?php

namespace Evirma\Bundle\CoreBundle\Domain\ValueObject;

use Evirma\Bundle\CoreBundle\Serializer\SerializerHandler;
use Evirma\Bundle\CoreBundle\Traits\SetGetExtraTrait;
use JsonSerializable;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

abstract class ValueObject implements JsonSerializable
{
    use SetGetExtraTrait;

    public static function factory($data)
    {
        if (is_null($data)) {
            return false;
        }

        $class = static::class;
        if ($data instanceof $class) {
            return $data;
        } elseif (is_array($data)) {
            return SerializerHandler::denormalizeArrayToObject($data, static::class);
        }

        return false;
    }

    public function toArray()
    {
        return SerializerHandler::objectToArray($this);
    }

    public function toJson()
    {
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());
        $serializer = new Serializer([$normalizer], [new JsonEncoder()]);

        return $serializer->serialize($this, 'json', ['json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT]);
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
