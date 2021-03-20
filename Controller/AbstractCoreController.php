<?php

namespace Evirma\Bundle\CoreBundle\Controller;

use Evirma\Bundle\AutotextBundle\Autotext;
use Evirma\Bundle\CoreBundle\Service\PageCache;
use Evirma\Bundle\CoreBundle\Service\PageMeta;
use Evirma\Bundle\CoreBundle\Service\RequestService;
use Evirma\Bundle\CoreBundle\Traits\CacheTrait;
use Evirma\Bundle\CoreBundle\Traits\DbAwareTrait;
use Evirma\Bundle\CoreBundle\Traits\LoggerTrait;
use Evirma\Bundle\CoreBundle\Traits\PagerTrait;
use Evirma\Bundle\CoreBundle\Traits\ServiceSystemTrait;
use Evirma\Bundle\CoreBundle\Traits\TranslatorTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class AbstractCoreController extends AbstractController
{
    use TranslatorTrait;
    use ServiceSystemTrait;
    use CacheTrait;
    use LoggerTrait;
    use DbAwareTrait;
    use PagerTrait;

    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                RequestService::class,
                PageMeta::class,
                PageCache::class,
                Autotext::class,
            ]
        );
    }

    /**
     * Returns a JsonResponse that uses the serializer component if enabled, or json_encode.
     *
     * @param       $data
     * @param int   $status
     * @param array $headers
     * @return JsonResponse
     */
    protected function jsonResponse($data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse(json_encode($data, JSON_UNESCAPED_UNICODE), $status, $headers, true);
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