<?php

namespace Evirma\Bundle\CoreBundle\Serializer;

use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SerializerHandler
{
    private static $arrayToObjectSerializer;
    private static $objectToArraySerializer;

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
            return self::getObjectToArraySerializer()->normalize($object);
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

    public static function getObjectToArraySerializer()
    {
        if (!self::$objectToArraySerializer) {
            self::$objectToArraySerializer = new Serializer([new ObjectNormalizer()]);
        }

        return self::$objectToArraySerializer;
    }
}