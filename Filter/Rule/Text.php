<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Filter\FilterRule;

class Text extends FilterRule
{
    public function filter($value)
    {
        $value = trim(preg_replace('#\s+#', ' ', $value));
        return trim($value);
    }
}