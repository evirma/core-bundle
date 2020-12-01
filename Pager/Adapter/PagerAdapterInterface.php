<?php

namespace Evirma\Bundle\CoreBundle\Pager\Adapter;


interface PagerAdapterInterface
{
    public function count(): int;
    public function getItems($offset, $length): iterable;
}
