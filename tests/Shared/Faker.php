<?php

namespace App\Tests\Shared;

use Faker\Factory;
use Faker\Generator;

class Faker
{
    public static function create() : Generator
    {
        return Factory::create();
    }
}