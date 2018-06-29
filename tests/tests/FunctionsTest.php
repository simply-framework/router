<?php

namespace Simply\Router;

use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    public function testInvalidPattern()
    {
        $this->expectException(\RuntimeException::class);
        string_split('/((?2))((?1))/', 'subject');
    }
}
