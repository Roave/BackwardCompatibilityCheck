<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use function array_intersect_key;
use function array_keys;
use function array_map;
use function Safe\array_combine;
use function strtolower;

final class MethodChanged implements ClassBased
{
    /** @var MethodBased */
    private $checkMethod;

    public function __construct(MethodBased $checkMethod)
    {
        $this->checkMethod = $checkMethod;
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass) : Changes
    {
        return Changes::fromIterator($this->checkSymbols($this->methods($fromClass), $this->methods($toClass)));
    }

    /**
     * @param ReflectionMethod[] $from
     * @param ReflectionMethod[] $to
     *
     * @return iterable|Change[]
     */
    private function checkSymbols(array $from, array $to) : iterable
    {
        foreach (array_keys(array_intersect_key($from, $to)) as $name) {
            yield from $this->checkMethod->__invoke($from[$name], $to[$name]);
        }
    }

    /** @return ReflectionMethod[] indexed by lower case method name */
    private function methods(ReflectionClass $class) : array
    {
        $methods = $class->getMethods();

        return array_combine(
            array_map(static function (ReflectionMethod $method) : string {
                return strtolower($method->getName());
            }, $methods),
            $methods
        );
    }
}
