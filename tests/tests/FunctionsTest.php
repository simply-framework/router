<?php

namespace Simply\Router;

use PHPUnit\Framework\TestCase;

/**
 * RouteDefinitionTest.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class FunctionsTest extends TestCase
{
    public function testInvalidPattern()
    {
        $this->expectException(\RuntimeException::class);
        string_split('/((?2))((?1))/', 'subject');
    }
}
