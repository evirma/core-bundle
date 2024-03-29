<?php

namespace Evirma\Bundle\CoreBundle\Traits;

use Evirma\Bundle\AutotextBundle\Autotext;
use Evirma\Bundle\CoreBundle\Entity\User;
use Evirma\Bundle\CoreBundle\Service\FileStorageService;
use Evirma\Bundle\CoreBundle\Service\PageMeta;
use Evirma\Bundle\CoreBundle\Service\RequestService;
use LogicException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;

/**
 * @property ContainerInterface $container
 */
trait ServiceSystemTrait
{
    /**
     * @deprecated
     * @return FileStorageService
     */
    public function getFileStorageService()
    {
        return $this->getService(FileStorageService::class);
    }

    /**
     * @deprecated
     * @return RequestService
     */
    public function getRequestService()
    {
        return $this->getService(RequestService::class);
    }

    /**
     * @deprecated
     * @return User|object|string
     */
    protected function getLoggedUser()
    {
        /** @var TokenInterface $token */
        if (null === $token = $this->getTokenStorage()->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }

        return $user;
    }

    /**
     * @deprecated
     * @return TokenStorage
     */
    protected function getTokenStorage()
    {
        return $this->getService('security.token_storage');
    }

    /**
     * @param string $class
     * @return object|mixed
     */
    protected function getService(string $class)
    {
        if (!$this->container->has($class)) {
            $message = "The {$class} is not registered in your application.";
            throw new LogicException($message);
        }

        return $this->container->get($class);
    }

    /**
     * @deprecated
     * @return Request
     */
    protected function getRequest()
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->getService('request_stack');

        return $requestStack->getCurrentRequest();
    }

    /**
     * @deprecated
     * @return Router
     */
    protected function getRouter()
    {
        return $this->getService('router');
    }

    /**
     * @deprecated
     * @return mixed|object|Environment
     */
    protected function getTwig()
    {
        return $this->getService('twig');
    }

    /**
     * @deprecated
     * @return Autotext
     */
    protected function getAutotext()
    {
        return $this->getService(Autotext::class);
    }

    /**
     * @deprecated
     * @return PageMeta|mixed
     */
    protected function getPageMeta()
    {
        return $this->getService(PageMeta::class);
    }

}
