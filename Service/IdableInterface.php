<?php

namespace Evirma\Bundle\CoreBundle\Service;

interface IdableInterface
{
    public function getId($entity);
    public function getDisplayId($entity);
}
