<?php

namespace Evirma\Bundle\CoreBundle\Traits;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @property ContainerInterface $container
 */
trait ValidatorTrait
{
    protected ValidatorInterface $validatorManager;

    /**
     * @required
     * @param ValidatorInterface $validator
     */
    public function setValidator(ValidatorInterface $validator)
    {
        $this->validatorManager = $validator;
    }

    /**
     * @return ValidatorInterface
     */
    protected function getValidator()
    {
        if (!isset($this->validatorManager)) {
            throw new InvalidArgumentException('ValidatorInterface must be defined');
        }

        return $this->validatorManager;
    }

    /**
     * @param      $value
     * @param null $constraints
     * @param null $groups
     * @return ConstraintViolation[]|ConstraintViolationList
     */
    protected function validateValue($value, $constraints = null, $groups = null)
    {
        /** @var ConstraintViolationList|ConstraintViolation[] $violationList */
        $violationList = $this->getValidator()->validate($value, $constraints, $groups);
        return $violationList;
    }

    /**
     * @param      $value
     * @param null $constraints
     * @param null $groups
     * @return bool|string|null
     */
    protected function isInvalidValue($value, $constraints = null, $groups = null)
    {
        $violationList = $this->validateValue($value, $constraints, $groups);
        foreach ($violationList as $violation) break;
        if (isset($violation)) return $violation->getMessage();
        return false;
    }


    /**
     * @param      $value
     * @param null $constraints
     * @param null $groups
     * @return bool
     */
    protected function isValidValue($value, $constraints = null, $groups = null)
    {
        $violationList = $this->validateValue($value, $constraints, $groups);
        foreach ($violationList as $violation) break;
        return !isset($violation);
    }
}
