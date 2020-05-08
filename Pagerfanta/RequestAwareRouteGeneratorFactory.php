<?php declare(strict_types=1);

namespace Evirma\Bundle\CoreBundle\Pagerfanta;

use BabDev\PagerfantaBundle\RouteGenerator\RouteGeneratorFactoryInterface;
use BabDev\PagerfantaBundle\RouteGenerator\RouteGeneratorInterface;
use BabDev\PagerfantaBundle\RouteGenerator\RouterAwareRouteGenerator;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RequestAwareRouteGeneratorFactory implements RouteGeneratorFactoryInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(UrlGeneratorInterface $router, RequestStack $requestStack)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function create(array $options = []): RouteGeneratorInterface
    {
        $options = array_replace(
            [
                'routeName' => null,
                'routeParams' => [],
                'pageParameter' => '[page]',
                'omitFirstPage' => true,
            ],
            $options
        );

        if (null === $options['routeName']) {
            $request = $this->getRequest();

            if (null !== $this->requestStack->getParentRequest()) {
                throw new RuntimeException('The request aware route generator can not guess the route when used in a sub-request, pass the "routeName" option to use this generator.');
            }

            $options['routeName'] = $request->attributes->get('_route');

            // Make sure we read the route parameters from the passed option array
            $defaultRouteParams = array_merge($request->query->all(), $request->attributes->get('_route_params', []));

            $options['routeParams'] = array_merge($defaultRouteParams, $options['routeParams']);
        }

        return new RouterAwareRouteGenerator($this->router, $options);
    }

    private function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }
}
