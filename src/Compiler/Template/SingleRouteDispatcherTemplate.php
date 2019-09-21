<?php

namespace Simply\Router\Compiler\Template;

use Riimu\Kit\PHPEncoder\PHPEncoder;
use Simply\Router\Compiler\CompilerRoute;
use Simply\Router\DispatcherInterface;
use Simply\Router\Parser\Segment\DynamicSegment;
use Simply\Router\Parser\Segment\PlaceholderSegment;
use Simply\Router\RegularExpressionValidator;

/**
 * SingleRouteDispatcherTemplate.
 * @author Riikka Kalliomäki <riikka.kalliomaki@gmail.com>
 * @copyright Copyright (c) 2019 Riikka Kalliomäki
 * @license http://opensource.org/licenses/mit-license.php MIT License
 */
class SingleRouteDispatcherTemplate implements DispatcherTemplateInterface
{
    private $encoder;

    public function __construct()
    {
        $this->encoder = new PHPEncoder([
            'array.inline' => 200,
            'array.eol' => "\n",
        ]);
    }

    /**
     * @param string $class
     * @param CompilerRoute[] $routes
     * @param int[][][] $staticPaths
     * @param string[] $methodCases
     * @return string
     */
    public function formatDispatcher(string $class, array $routes, array $staticPaths, array $methodCases): string
    {
        $className = $this->compileClassName($class);
        $dispatcherCases = [];

        foreach ($methodCases as $method => $switchStatement) {
            $dispatcherCases[$method] = $this->compileDispatcherCase($staticPaths[$method] ?? [], $switchStatement);
        }

        $formatCases = array_map(function (CompilerRoute $route): string {
            return sprintf('return %s;', $this->encoder->encode([
                $route->getPath()->getFormat(),
                $route->getPath()->getParameterNames(),
            ]));
        }, $routes);

        $compiledDispatcher = $this->addIndent($this->addIndent($this->compileSwitch('$method', $dispatcherCases)));
        $compiledFormats = $this->addIndent($this->addIndent($this->compileSwitch('$name', $formatCases)));

        return <<<PHP
            <?php

            $className
            {
                public function dispatch(string \$method, string \$path): array
                {
                    $compiledDispatcher

                    return \$this->dispatchMiss(\$method, \$path);
                }
                
                public function getFormat(string \$name): array
                {
                    $compiledFormats
                    
                    return [null, null];
                }
            }

            PHP;
    }

    private function compileClassName(string $class): string
    {
        $pos = strrpos($class, '\\');

        if ($pos === false) {
            return "class $class extends \Simply\Router\AbstractDispatcher";
        }

        $namespace = substr($class, 0, $pos);
        $basename = substr($class, $pos + 1);

        return <<<PHP
            namespace $namespace;
            
            use Simply\Router\AbstractDispatcher;
            
            class $basename extends AbstractDispatcher
            PHP;
    }

    private function compileDispatcherCase(array $statics, string $nodes): string
    {
        return $this->appendLines([
            $statics ? $this->compileSwitch('$path', array_map([$this, 'formatRoutes'], $statics)) : null,
            '$parts = \preg_split(\'#/#\', $path, -1, \PREG_SPLIT_NO_EMPTY);',
            $nodes
        ]);
    }

    public function formatCountSwitch(array $cases): string
    {
        return $this->compileSwitch('\count($parts)', $cases);
    }

    public function formatStaticNode(int $index, array $static, ?string $skip): string
    {
        return $this->appendLines([
            $static ? $this->compileSwitch("\$parts[$index]", $static) : null,
            $skip,
        ]);
    }

    public function formatDynamicNode(int $index, array $dynamic, ?string $skip): string
    {
        return $this->appendLines([
            $dynamic ? $this->compiledDynamicSegments($index, $dynamic) : null,
            $skip,
        ]);
    }

    protected function compiledDynamicSegments(int $index, array $dynamicSegments): string
    {
        if (count($dynamicSegments) === 1) {
            $condition = $this->compilePatternMatch(key($dynamicSegments), $index);
            $code = $this->addIndent(current($dynamicSegments));

            return <<<PHP
                if ($condition) {
                    $code
                }
                PHP;
        }

        $patterns = [];
        $cases = [];

        foreach ($dynamicSegments as $pattern => $code) {
            $patterns[] = $pattern;
            $cases[] = $code;
        }

        $combined = $this->combinePatterns($patterns);

        if (count($combined) === 1) {
            $clause = $this->compilePatternMatch(current($combined), $index);
        } else {
            $clauses = [];

            foreach ($combined as $pattern) {
                $clauses[] = $this->compilePatternMatch($pattern, $index);
            }

            $clause = sprintf("\n    %s\n", $this->addIndent(implode(" ||\n", $clauses)));
        }

        $switch = $this->addIndent($this->compileSwitch("\$match[$index]['MARK']", $cases));

        return <<<PHP
                if ($clause) {
                    $switch
                }
                PHP;
    }

    protected function compilePatternMatch(string $pattern, int $index): string
    {
        return sprintf('\preg_match(%s, $parts[%d], $match[%2$d])', $this->encoder->encode($pattern), $index);
    }

    private function combinePatterns(array $patterns): array
    {
        $results = [];
        $index = 0;

        $combine = static function (array $patterns): string {
            return sprintf('/(?|%s)/', implode('|', $patterns));
        };

        foreach ($patterns as $id => $pattern) {
            $results[$index][] = substr($pattern, 1, -1) . "(*MARK:$id)";

            if (!RegularExpressionValidator::isValid($combine($results[$index]))) {
                $results[$index + 1] = [array_pop($results[$index])];
                $index++;
            }
        }

        return array_map($combine, $results);
    }

    /**
     * @param CompilerRoute[] $routes
     * @return string
     */
    public function formatRoutes(array $routes): string
    {
        if (count($routes) !== 1) {
            throw new \InvalidArgumentException('Multiple identical routes are not supported by the template');
        }

        $returnFormat = 'return [%d, %s, [%s], %s];';
        $placeholderFormat = '%s => $parts[%d]';
        $dynamicFormat = '%s => $match[%d][%1$s]';

        $route = array_shift($routes);
        $segments = $route->getPath()->getSegments();
        $params = [];

        foreach ($segments as $index => $segment) {
            if ($segment instanceof PlaceholderSegment) {
                $params[] = sprintf($placeholderFormat, $this->encoder->encode($segment->getName()), $index);
            } elseif ($segment instanceof DynamicSegment) {
                foreach ($segment->getNames() as $name) {
                    $params[] = sprintf($dynamicFormat, $this->encoder->encode($name), $index);
                }
            }
        }

        $handler = $this->encoder->encode($route->getRoute()->getHandler());
        $name = $this->encoder->encode($route->getId());

        return sprintf($returnFormat, DispatcherInterface::FOUND, $handler, implode(', ', $params), $name);
    }

    private function compileSwitch(string $condition, array $cases): string
    {
        if (count($cases) === 1) {
            $value = $this->encoder->encode(key($cases));
            $code = $this->addIndent(current($cases));

            return <<<PHP
                if ($condition === $value) {
                    $code
                }
                PHP;
        }

        ksort($cases);
        $compiled = [];
        $last = array_key_last($cases);

        foreach ($cases as $case => $code) {
            $value = $this->encoder->encode($case);
            $code = $this->addIndent($code);

            if ($case === $last || substr($code, -2) === '];') {
                $compiled[] = <<<PHP
                    case $value:
                        $code
                    PHP;
            } else {
                $compiled[] = <<<PHP
                    case $value:
                        $code
                        break;
                    PHP;
            }
        }

        $compiledCases = $this->addIndent(implode("\n", $compiled));

        return <<<PHP
            switch ($condition) {
                $compiledCases
            }
            PHP;
    }

    private function appendLines(array $lines): string
    {
        return implode("\n\n", array_filter($lines));
    }

    protected function addIndent(string $code): string
    {
        return preg_replace('/\R/', '$0    ', $code);
    }
}
