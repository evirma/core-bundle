<?php

namespace Evirma\Bundle\CoreBundle\Service;

use ReflectionException;
use ReflectionMethod;
use Symfony\Bundle\MakerBundle\Str;

class SetGetAccessor
{
    public const ATTRIBUTES = 'attributes';
    public const IGNORED_ATTRIBUTES = 'ignored_attributes';

    private static array $cache = [];

    public static function getAttributeValue(object $object, string $attribute)
    {
        $ucfirsted = Str::asCamelCase($attribute);

        $key = get_class($object).':getter'.$ucfirsted;

        if (isset(self::$cache[$key])) {
            $getter = self::$cache[$key];
            if ($getter == 'unknown') {
                return null;
            }

            return $object->$getter();
        }

        try {
            $getter = 'get'.$ucfirsted;
            if (is_callable([$object, $getter]) && self::isGetMethod(new ReflectionMethod($object, $getter))) {
                self::$cache[$key] = $getter;

                return $object->$getter();
            }

            $isser = 'is'.$ucfirsted;
            if (is_callable([$object, $isser]) && self::isGetMethod(new ReflectionMethod($object, $isser))) {
                self::$cache[$key] = $isser;

                return $object->$isser();
            }

            $haser = 'has'.$ucfirsted;
            if (is_callable([$object, $haser]) && self::isGetMethod(new ReflectionMethod($object, $haser))) {
                self::$cache[$key] = $haser;

                return $object->$haser();
            }
        } catch (ReflectionException $e) {
            self::$cache[$key] = 'unknown';

            return null;
        }

        self::$cache[$key] = 'unknown';

        return null;
    }

    public static function setAttributeValue(object $object, string $attribute, $value)
    {
        $setter = 'set'.Str::asCamelCase($attribute);
        $key = get_class($object).':'.$setter;

        if (!isset(self::$cache[$key])) {
            try {
                $method = new ReflectionMethod($object, $setter);
                self::$cache[$key] = is_callable([$object, $setter]) && !$method->isStatic();
            } catch (ReflectionException) {
                self::$cache[$key] = false;
            }
        }

        if (self::$cache[$key]) {
            $object->$setter($value);
        }

        return self::$cache[$key];
    }

    /**
     * Checks if a method's name is get.* or is.*, and can be called without parameters.
     *
     * @param ReflectionMethod $method
     * @return bool
     */
    private static function isGetMethod(ReflectionMethod $method): bool
    {
        $methodLength = strlen($method->name);

        return
            !$method->isStatic() &&
            (
                ((str_starts_with($method->name, 'get') && 3 < $methodLength) ||
                    (str_starts_with($method->name, 'is') && 2 < $methodLength) ||
                    (str_starts_with($method->name, 'has') && 3 < $methodLength)) &&
                0 === $method->getNumberOfRequiredParameters()
            );
    }

    public static function getCache()
    {
        return self::$cache;
    }
}
