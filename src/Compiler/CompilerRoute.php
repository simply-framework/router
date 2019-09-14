<?php

namespace Simply\Router\Compiler;

use Simply\Router\Collector\CollectedRoute;
use Simply\Router\Parser\ParsedPath;
use Simply\Router\Parser\Segment\SegmentInterface;

/**
 * CompilerPath.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class CompilerRoute
{
    private $id;
    private $route;
    private $path;

    public function __construct(string $id, CollectedRoute $route, ParsedPath $path)
    {
        $this->id = $id;
        $this->route = $route;
        $this->path = $path;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getRoute(): CollectedRoute
    {
        return $this->route;
    }

    public function getPath(): ParsedPath
    {
        return $this->path;
    }
}
