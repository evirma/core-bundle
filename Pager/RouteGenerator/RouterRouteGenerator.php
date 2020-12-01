<?php

namespace Evirma\Bundle\CoreBundle\Pager\RouteGenerator;

use InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RouterRouteGenerator
{
    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var array
     */
    private array $options;

    /**
     * @param UrlGeneratorInterface $router
     * @param array                 $options
     */
    public function __construct(UrlGeneratorInterface $router, array $options = [])
    {
        // Check missing options
        if (!isset($options['route'])) {
            throw new InvalidArgumentException(sprintf('The "%s" class options requires a "route" parameter to be set.', self::class));
        }

        $this->router = $router;
        $this->options = $options;
    }

    public function __invoke(int $page): string
    {
        $pageParameter = $this->options['page_parameter'] ?? '[page]';
        $omitFirstPage = $this->options['omit_first_page'] ?? true;
        $routeParams = $this->options['route_params'] ?? [];

        $pagePropertyPath = new PropertyPath($pageParameter);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        if ($omitFirstPage) {
            $propertyAccessor->setValue($routeParams, $pagePropertyPath, $page > 1 ? $page : null);
        } else {
            $propertyAccessor->setValue($routeParams, $pagePropertyPath, $page);
        }

        return $this->router->generate($this->options['route'], $routeParams);
    }
}
