<?php

namespace Evirma\Bundle\CoreBundle\Domain;

use Evirma\Bundle\CoreBundle\Filter\FilterStatic;
use Evirma\Bundle\CoreBundle\Filter\Rule\SuggestionSearch;
use Evirma\Bundle\CoreBundle\Filter\Rule\SuggestionSearchId;

abstract class AbstractSuggestion implements SuggestionInterface
{
    /**
     * @param array $fields
     * @param       $searchText
     * @return string
     */
    protected function prepareSuggestionsLike(array $fields, $searchText)
    {
        $result = [];
        $preparedSearchText = FilterStatic::filterValue($searchText, SuggestionSearch::class);
        foreach ($fields as $field) {
            $result[] = "lower($field) LIKE '$preparedSearchText'";
        }

        if ($id = FilterStatic::filterValue($searchText, SuggestionSearchId::class)) {
            $result[] = 'id = '.$id;
        }

        return implode(' OR ', $result);
    }

    /**
     * @param array|null $data
     * @param string     $field
     * @return array
     */
    protected function sortNatural(array $data = null, $field = 'text')
    {
        if (!$data || (count($data) == 1)) {
            return $data;
        }

        usort(
            $data,
            function ($a, $b) use ($field) {
                return strnatcasecmp($a[$field], $b[$field]); //Case insensitive
            }
        );

        return $data;
    }
}
