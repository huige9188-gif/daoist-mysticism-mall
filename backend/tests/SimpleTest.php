<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    public function testSimple()
    {
        $this->assertTrue(true);
    }
    
    public function testJsonFunction()
    {
        $response = json(['test' => 'data']);
        $this->assertNotNull($response);
    }
}
