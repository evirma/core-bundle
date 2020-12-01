<?php

namespace Evirma\Bundle\CoreBundle\Pager\Template;

use Evirma\Bundle\CoreBundle\Pager\Pager;

interface PagerTemplateInterface
{
    /**
     * The route generator can be any callable to generate the routes receiving the page number as first and unique argument.
     *
     * @param Pager    $pager
     * @param callable $routeGenerator
     * @param array    $options
     * @return string
     */
    public function render(Pager $pager, $routeGenerator, array $options = []);

    /**
     * Returns the canonical name.
     *
     * @return string
     */
    public function getName();
}
