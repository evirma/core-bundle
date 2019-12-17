<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

class StopwordsToArray extends StopwordsUniq
{
    public function filter($value)
    {
        $value = parent::filter($value);

        return explode("\n", $value);
    }
}