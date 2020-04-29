<?php

namespace Evirma\Bundle\CoreBundle\Filter;

use Exception;

class FilterStatic
{
    private static $filters = [];

    /**
     * @param array $valuesArray
     * @param       $filter
     * @return array
     */
    public static function filterValuesArray($valuesArray, $filter)
    {
        if (is_array($valuesArray)) {
            $valuesArray = array_map(
                function ($string) use ($filter) {
                    return FilterStatic::filterValue($string, $filter);
                },
                $valuesArray
            );
        }

        return $valuesArray;
    }

    /**
     * @param $value
     * @param $filter
     * @return mixed
     */
    public static function filterValue($value, $filter)
    {
        if ($filter instanceof FilterRule) {
            return $filter->transform($value);
        } elseif (is_string($filter)) {
            try {
                return self::getFilter($filter)->transform($value);
            } catch (Exception $e) {
                return $value;
            }
        }

        return self::walkRuleChain($value, $filter);
    }

    /**
     * @param $filter
     * @return FilterRule
     * @throws Exception
     */
    protected static function getFilter($filter)
    {
        if (isset(self::$filters[$filter])) {
            return self::$filters[$filter];
        }

        if (!class_exists($filter)) {
            throw new Exception('Filter not found: '.$filter);
        }

        self::$filters[$filter] = new $filter;

        return self::$filters[$filter];
    }

    /**
     * @param mixed              $value
     * @param array|FilterRule[] $filters
     * @return mixed
     */
    protected static function walkRuleChain($value, $filters)
    {
        /** @var array|FilterRule[] $filters */
        foreach ($filters as $filter) {
            $value = $filter->transform($value);
        }

        return $value;
    }
}