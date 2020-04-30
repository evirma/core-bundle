<?php

namespace Evirma\Bundle\CoreBundle\Traits;

trait TransformerMappingTrait
{
    public $groups = [];

    /**
     * @return array
     */
    public function getGroups(): array
    {
        if (empty($this->groups)) {
            $this->groups = ['Default'];
        }

        return $this->groups;
    }

    /**
     * @param array $groups
     * @return $this
     */
    public function setGroups(array $groups = [])
    {
        $this->groups = $groups;

        return $this;
    }

    public function reverseTransform($value)
    {
        return $value;
    }
}
