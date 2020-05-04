<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Filter\FilterRule;
use Evirma\Bundle\CoreBundle\Filter\FilterStatic;
use Evirma\Bundle\CoreBundle\Util\StringUtil;

class Gender extends FilterRule
{
    public function filter($value)
    {
        $value = StringUtil::lower(FilterStatic::filterValue($value, HtmlAndUnicode::class));

        switch ($value) {
            case 'f':
            case 'female':
            case 'женский':
            case 'женский род':
            case 'ж':
            case 'жен':
            case 'жен.':
                return 'F';
            case 'm':
            case 'male':
            case 'мужской':
            case 'мужской род':
            case 'м':
            case 'муж':
            case 'муж.':
                return 'M';
            case 'n':
            case 'neuter':
            case 'средний':
            case 'средний род':
            case 'с':
            case 'сред':
            case 'сред.':
            case 'ср':
            case 'ср.':
                return 'N';
        }

        return null;
    }
}