<?php

namespace Evirma\Bundle\CoreBundle\Service;

use ReflectionException;
use ReflectionMethod;
use function is_callable;
use function strlen;

class SetGetAccessor
{
    public const ATTRIBUTES = 'attributes';
    public const IGNORED_ATTRIBUTES = 'ignored_attributes';

    private static $cache = [];

    public static function getAttributeValue(object $object, string $attribute)
    {
        $ucfirsted = ucfirst($attribute);

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
        $setter = 'set'.ucfirst($attribute);
        $key = get_class($object).':'.$setter;

        if (!isset(self::$cache[$key])) {
            try {
                self::$cache[$key] = is_callable([$object, $setter]) && !(new ReflectionMethod($object, $setter))->isStatic();
            } catch (ReflectionException $e) {
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
                ((0 === strpos($method->name, 'get') && 3 < $methodLength) ||
                    (0 === strpos($method->name, 'is') && 2 < $methodLength) ||
                    (0 === strpos($method->name, 'has') && 3 < $methodLength)) &&
                0 === $method->getNumberOfRequiredParameters()
            );
    }

    public static function getCache()
    {
        return self::$cache;
    }
}
