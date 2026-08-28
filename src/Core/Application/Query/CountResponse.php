<?php

namespace App\Core\Application\Query;

interface CountResponse
{
    /**
     * @return integer
     */
    public function getTotal(): int;
}