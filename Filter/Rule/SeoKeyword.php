<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Filter\FilterRule;
use Evirma\Bundle\CoreBundle\Filter\FilterStatic;

class SeoKeyword extends FilterRule
{
    public function filter($value)
    {
        $value = FilterStatic::filterValue($value, HtmlAndUnicode::class);
        $value = mb_strtolower(preg_replace('#\s+#', ' ', $value), 'UTF-8');
        $words = explode(' ', $value);
        $words = array_map([$this, 'filterWord'], $words);

        return implode(' ', $words);
    }

    private function filterWord($word)
    {
        return trim($word, '"\'()[]{}|-+=');
    }
}

