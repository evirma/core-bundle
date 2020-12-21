<?php

namespace Evirma\Bundle\CoreBundle\Traits;

use Evirma\Bundle\CoreBundle\Service\ObjectTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

trait ObjectTransformerTrait
{
    /**
     * @var ObjectTransformer
     */
    protected ObjectTransformer $objectTransformer;

    /**
     * @return ObjectTransformer
     */
    protected function getObjectTransformer(): ObjectTransformer
    {
        return $this->objectTransformer;
    }

    /**
     * @required
     * @param ObjectTransformer $objectTransformer
     * @return $this
     */
    public function setObjectTransformer(ObjectTransformer $objectTransformer)
    {
        $this->objectTransformer = $objectTransformer;

        return $this;
    }

    protected function registerFormTransformListener(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                return $this->getObjectTransformer()->transform($event->getData());
            }
        );

        return $builder;
    }
}
