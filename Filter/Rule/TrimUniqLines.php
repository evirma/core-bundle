<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Evirma\Bundle\CoreBundle\Filter\FilterRule;

class TrimUniqLines extends FilterRule
{
    public function filter($value)
    {
        $value = explode("\n", $value);
        $value = array_map('trim', $value);
        $value = array_filter($value);
        $value = array_unique($value);
        return implode("\n", $value);
    }
}