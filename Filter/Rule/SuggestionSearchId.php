<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Filter\FilterRule;
use Evirma\Bundle\CoreBundle\Filter\FilterStatic;

class SuggestionSearchId extends FilterRule
{
    public function filter($value)
    {
        $value = FilterStatic::filterValue($value, Name::class);
        if (preg_match('/#(\d+)/usi', $value, $m)) {
            return $m[1];
        }
        if (preg_match('#^\d+$#', $value, $m)) {
            return $m;
        }

        return null;
    }
}