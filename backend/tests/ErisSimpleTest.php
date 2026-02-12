<?php

namespace Tests;

use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

class ErisSimpleTest extends TestCase
{
    use TestTrait;
    
    public function testSimpleProperty()
    {
        $this->forAll(
            \Eris\Generator\choose(1, 100)
        )->then(function ($number) {
            $this->assertGreaterThan(0, $number);
        });
    }
}
