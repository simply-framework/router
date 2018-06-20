<?php

namespace Simply\Router;

use PHPUnit\Framework\TestCase;

/**
 * RouterDefinitionTest.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2018 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class RouterDefinitionProviderTest extends TestCase
{
    public function testDuplicateRouteNames()
    {
        $provider = new RouteDefinitionProvider();
        $provider->addRouteDefinition(new RouteDefinition('test', ['GET'], '/', 'test'));

        $this->expectException(\InvalidArgumentException::class);
        $provider->addRouteDefinition(new RouteDefinition('test', ['GET'], '/', 'test'));
    }

    public function testFetchingInvalidRouteId()
    {
        $provider = new RouteDefinitionProvider();

        $this->expectException(\InvalidArgumentException::class);
        $provider->getRouteDefinition(1);
    }

    public function testFetchingInvalidRouteName()
    {
        $provider = new RouteDefinitionProvider();

        $this->expectException(\InvalidArgumentException::class);
        $provider->getRouteDefinitionByName('test');
    }

    public function testPackedCountArray()
    {
        $provider = new RouteDefinitionProvider();

        $provider->addRouteDefinition(new RouteDefinition('test.a', ['GET'], '/{param}/to/route/', 'handler'));
        $provider->addRouteDefinition(new RouteDefinition('test.b', ['GET'], '/{param}/', 'handler'));

        $this->assertSame([0, 1, 2, 3], array_keys($provider->getSegmentCounts()));
    }
}