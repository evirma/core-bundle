<?php

namespace Evirma\Bundle\CoreBundle\Traits;

use Evirma\Bundle\CoreBundle\Service\ObjectTransformer;

trait ObjectTransformerTrait
{
    /**
     * @var ObjectTransformer
     */
    protected ObjectTransformer $objectTransformer;

    /**
     * @return ObjectTransformer
     */
    public function getObjectTransformer(): ObjectTransformer
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

}
