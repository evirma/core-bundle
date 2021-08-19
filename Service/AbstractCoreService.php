<?php

namespace Evirma\Bundle\CoreBundle\Service;

use Doctrine\Persistence\ManagerRegistry;
use Evirma\Bundle\CoreBundle\Filter\FilterStatic;
use Evirma\Bundle\CoreBundle\Filter\Rule\SuggestionSearch;
use Evirma\Bundle\CoreBundle\Filter\Rule\SuggestionSearchId;
use Evirma\Bundle\CoreBundle\Traits\CacheTrait;
use Evirma\Bundle\CoreBundle\Traits\DbAwareTrait;
use Evirma\Bundle\CoreBundle\Traits\LoggerTrait;
use Evirma\Bundle\CoreBundle\Traits\PagerTrait;
use Evirma\Bundle\CoreBundle\Traits\ServiceSystemTrait;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;

abstract class AbstractCoreService implements ServiceSubscriberInterface
{
    use CacheTrait;
    use ServiceSystemTrait;
    use LoggerTrait;
    use DbAwareTrait;
    use PagerTrait;

    protected $container;

    #[ArrayShape([
        'validator' => "string",
        'router' => "string",
        'request_stack' => "string",
        'http_kernel' => "string",
        'security.authorization_checker' => "string",
        'twig' => "string",
        'doctrine' => "string",
        'security.token_storage' => "string",
        'parameter_bag' => "string"
    ])] public static function getSubscribedServices()
    {
        return [
            'validator' => '?'.ValidatorInterface::class,
            'router' => '?'.RouterInterface::class,
            'request_stack' => '?'.RequestStack::class,
            'http_kernel' => '?'.HttpKernelInterface::class,
            'security.authorization_checker' => '?'.AuthorizationCheckerInterface::class,
            'twig' => '?'.Environment::class,
            'doctrine' => '?'.ManagerRegistry::class,
            'security.token_storage' => '?'.TokenStorageInterface::class,
            'parameter_bag' => ContainerBagInterface::class,
        ];
    }

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param string      $route         The name of the route
     * @param mixed       $parameters    An array of parameters
     * @param int $referenceType The type of reference (one of the constants in UrlGeneratorInterface)
     *
     * @return string The generated URL
     *
     * @see UrlGeneratorInterface
     */
    public function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }

    /**
     * Returns a rendered view.
     *
     * @param string $view       The view name
     * @param array  $parameters An array of parameters to pass to the view
     * @return string The rendered view
     */
    public function renderView($view, array $parameters = array())
    {
        try {
            return $this->container->get('twig')->render($view, $parameters);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @param array $fields
     * @param       $searchText
     * @return string
     */
    protected function prepareSuggestionsLike(array $fields, $searchText)
    {
        $result = [];
        $preparedSearchText = FilterStatic::filterValue($searchText, SuggestionSearch::class);
        foreach ($fields as $field) {
            $result[] = "lower($field) LIKE '$preparedSearchText'";
        }

        if ($id = FilterStatic::filterValue($searchText, SuggestionSearchId::class)) {
            $result[] = 'id = ' . $id;
        }

        return implode(' OR ', $result);
    }
}