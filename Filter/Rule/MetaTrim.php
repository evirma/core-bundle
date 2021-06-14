<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Filter\FilterRule;
use Evirma\Bundle\CoreBundle\Filter\FilterStatic;
use Evirma\Bundle\CoreBundle\Util\StringUtil;

class MetaTrim extends FilterRule
{
    public function filter($value)
    {
        $value = FilterStatic::filterValue($value, HtmlAndUnicode::class);
        $value = preg_replace('#\s*([;:.,!?])#usi', '\\1', $value);
        $value = trim(preg_replace('#\s+#', ' ', $value));
        $value = preg_replace('#[.]+#usi', '.', $value);

        return StringUtil::ucfirst($value);
    }
}