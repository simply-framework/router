<?php

namespace Simply\Router;

use PHPUnit\Framework\TestCase;

/**
 * RouteDefinitionTest.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018-2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class RouteDefinitionTest extends TestCase
{
    public function testInvalidHandlerValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        new RouteDefinition('test', ['GET'], '/', new \stdClass());
    }

    public function testInvalidHandlerValueInArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        new RouteDefinition('test', ['GET'], '/', [new \stdClass()]);
    }

    public function testInvalidHttpMethod()
    {
        $this->expectException(\InvalidArgumentException::class);
        new RouteDefinition('test', ['NOT_HTTP_METHOD'], '/', 'foobar');
    }

    public function testInvalidRegularExpression()
    {
        $this->expectException(\InvalidArgumentException::class);
        new RouteDefinition('test', ['GET'], '/path/{param:\d[1-0]}/', 'foobar');
    }

    public function testFailingRegularExpression()
    {
        $this->expectException(\InvalidArgumentException::class);
        new RouteDefinition('test', ['GET'], '/path/{param:((?2))((?1))}/path/', 'foobar');
    }

    public function testDuplicateParameterName()
    {
        $this->expectException(\InvalidArgumentException::class);
        new RouteDefinition('test', ['GET'], '/path/{param}/{param}/', 'foobar');
    }

    public function testMissingRouteParameter()
    {
        $definition = new RouteDefinition('test', ['GET'], '/path/{param}/route/', 'foobar');

        $this->expectException(\InvalidArgumentException::class);
        $definition->formatUrl([]);
    }

    public function testExtraRouteParameter()
    {
        $definition = new RouteDefinition('test', ['GET'], '/path/{param}/route/', 'foobar');

        $this->expectException(\InvalidArgumentException::class);
        $definition->formatUrl(['param' => 'foo', 'other' => 'bar']);
    }
}
