<?php

namespace Evirma\Bundle\CoreBundle\Serializer;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SerializerHandler
{
    private static $arrayToObjectSerializer;
    private static $objectSerializer;

    public static function denormalizeArrayToObject(array $data, $objectName)
    {
        try {
            return self::getArrayToObjectSerialize()->denormalize($data, $objectName);
        } catch (ExceptionInterface $e) {
            return false;
        }
    }

    public static function objectToArray($object)
    {
        try {
            return self::getObjectSerializer()->normalize($object);
        } catch (ExceptionInterface $e) {
            return false;
        }
    }

    public static function objectToJson($object, $encodeOptions = JSON_UNESCAPED_UNICODE)
    {
        try {
            return self::getObjectSerializer()->serialize($object, 'json', ['json_encode_options' => $encodeOptions]);
        } catch (ExceptionInterface $e) {
            return false;
        }
    }

    public static function getArrayToObjectSerialize()
    {
        if (!self::$arrayToObjectSerializer) {
            self::$arrayToObjectSerializer = new Serializer([new ObjectNormalizer()]);
        }

        return self::$arrayToObjectSerializer;
    }

    public static function getObjectSerializer()
    {
        if (!self::$objectSerializer) {
            $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());
            self::$objectSerializer = new Serializer([$normalizer], [new JsonEncoder()]);
        }

        return self::$objectSerializer;
    }
}