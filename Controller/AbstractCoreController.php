<?php

namespace Evirma\Bundle\CoreBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Evirma\Bundle\CoreBundle\Service\PageCache;
use Evirma\Bundle\CoreBundle\Service\PageMeta;
use Evirma\Bundle\CoreBundle\Service\RequestService;
use Evirma\Bundle\CoreBundle\Traits\CacheTrait;
use Evirma\Bundle\CoreBundle\Traits\DbTrait;
use Evirma\Bundle\CoreBundle\Traits\LoggerTrait;
use Evirma\Bundle\CoreBundle\Traits\PagerTrait;
use Evirma\Bundle\CoreBundle\Traits\ServiceSystemTrait;
use Evirma\Bundle\CoreBundle\Traits\TranslatorTrait;
use Meniam\AutotextBundle\Autotext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;

abstract class AbstractCoreController extends AbstractController
{
    use TranslatorTrait;
    use ServiceSystemTrait;
    use CacheTrait;
    use LoggerTrait;
    use DbTrait;
    use PagerTrait;

    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            RequestService::class,
            EntityManagerInterface::class,
            PageMeta::class,
            PageCache::class,
            Autotext::class
        ]);
    }

    /**
     * CreateForm And HandleRequest
     *
     * @param string $type
     * @param null   $data
     * @param array  $options
     * @return FormInterface
     */
    protected function handleForm(string $type, $data = null, array $options = []): FormInterface
    {
        $form = $this->createForm($type, $data, $options);
        $form->handleRequest($this->getRequest());
        return $form;
    }

    /**
     * @return bool
     */
    protected function isProd()
    {
        return $this->getParameter('kernel.environment') == 'prod';
    }
}