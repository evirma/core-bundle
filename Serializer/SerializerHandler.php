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

    public static function denormalizeArrayToObject(array $data, $objectName, array $context = [])
    {
        try {
            return self::getArrayToObjectSerialize()->denormalize($data, $objectName, null, $context);
        } catch (ExceptionInterface $e) {
            return false;
        }
    }

    public static function objectToArray($object, array $context = [])
    {
        try {
            return self::getObjectSerializer($context)->normalize($object, null, $context);
        } catch (ExceptionInterface $e) {
            return false;
        }
    }

    public static function objectToJson($object, $encodeOptions = JSON_UNESCAPED_UNICODE, array $context = [])
    {
        try {
            $context['json_encode_options'] = $encodeOptions;

            return self::getObjectSerializer()->serialize($object, 'json', $context);
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

    public static function getObjectSerializer(array $context = [])
    {
        if (!self::$objectSerializer) {
            $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());
            self::$objectSerializer = new Serializer([$normalizer], [new JsonEncoder()]);
        }

        return self::$objectSerializer;
    }
}