<?php

namespace App\Tests\Support;

use ReflectionClass;

trait ReflectionTestTrait
{
    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionClass($object);
        $reflection->getProperty($property)->setValue($object, $value);
    }
}
