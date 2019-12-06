<?php

namespace Evirma\Bundle\CoreBundle\Filter\Rule;

use Cocur\Slugify\Slugify;

class Slug extends Name
{
    /**
     * @var Slugify
     */
    private static $slugify;

    public function filter($value)
    {
        return $this->getSlugify()->slugify(parent::filter($value));
    }

    /**
     * @return Slugify
     */
    private function getSlugify()
    {
        if (!self::$slugify) {
            self::$slugify = new Slugify();
        }

        return self::$slugify;
    }
}

