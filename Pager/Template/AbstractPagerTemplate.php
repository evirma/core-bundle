<?php

namespace Evirma\Bundle\CoreBundle\Pager\Template;

use \InvalidArgumentException;
use \RuntimeException;
use function gettype;
use function is_callable;

abstract class AbstractPagerTemplate implements PagerTemplateInterface
{
    /**
     * @var callable|null
     */
    private $routeGenerator;

    /**
     * @var array
     */
    protected array $options = [];

    /**
     * @param string $name The name of the option to look up
     *
     * @return mixed The option value if it exists
     *
     * @throws InvalidArgumentException if the option does not exist
     */
    protected function option($name)
    {
        if (!isset($this->options[$name])) {
            throw new InvalidArgumentException(sprintf('The option "%s" does not exist.', $name));
        }

        return $this->options[$name];
    }

    /**
     * @param callable $routeGenerator
     * @throws InvalidArgumentException if the route generator is not a callable
     */
    public function setRouteGenerator($routeGenerator): void
    {
        if (!is_callable($routeGenerator)) {
            throw new InvalidArgumentException(sprintf('The $routeGenerator argument of %s() must be a callable, a %s was given.', __METHOD__, gettype($routeGenerator)));
        }

        $this->routeGenerator = $routeGenerator;
    }

    /**
     * @throws RuntimeException if the route generator has not been set
     */
    protected function getRouteGenerator(): callable
    {
        if (!$this->routeGenerator) {
            throw new RuntimeException(sprintf('The route generator was not set to the template, ensure you call %s::setRouteGenerator().', static::class));
        }

        return $this->routeGenerator;
    }

    /**
     * Generate the route (URL) for the given page.
     *
     * @param int $page
     *
     * @return string
     */
    protected function generateRoute($page)
    {
        $generator = $this->getRouteGenerator();

        return $generator($page);
    }
}
