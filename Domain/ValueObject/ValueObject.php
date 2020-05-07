<?php

namespace Evirma\Bundle\CoreBundle\Domain\ValueObject;

use Evirma\Bundle\CoreBundle\Traits\SetGetExtraTrait;
use JsonSerializable;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

abstract class ValueObject implements JsonSerializable
{
    use SetGetExtraTrait;

    public function __toArray()
    {
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());
        $serializer = new Serializer([$normalizer], [new JsonEncoder()]);

        return $serializer->serialize($this, 'json', ['json_encode_options' => JSON_UNESCAPED_UNICODE]);
    }

    public function jsonSerialize()
    {
        return $this->__toArray();
    }
}
