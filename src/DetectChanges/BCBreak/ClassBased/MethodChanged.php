<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use Psl\Dict;
use Psl\Str;
use Psl\Vec;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

final class MethodChanged implements ClassBased
{
    private MethodBased $checkMethod;

    public function __construct(MethodBased $checkMethod)
    {
        $this->checkMethod = $checkMethod;
    }

    public function __invoke(ReflectionClass $fromClass, ReflectionClass $toClass): Changes
    {
        return Changes::fromIterator($this->checkSymbols($this->methods($fromClass), $this->methods($toClass)));
    }

    /**
     * @param ReflectionMethod[] $from
     * @param ReflectionMethod[] $to
     *
     * @return iterable|Change[]
     */
    private function checkSymbols(array $from, array $to): iterable
    {
        foreach (Vec\keys(Dict\intersect_by_key($from, $to)) as $name) {
            yield from $this->checkMethod->__invoke($from[$name], $to[$name]);
        }
    }

    /** @return ReflectionMethod[] indexed by lower case method name */
    private function methods(ReflectionClass $class): array
    {
        $methods = $class->getMethods();

        return Dict\associate(
            Vec\map($methods, static function (ReflectionMethod $method): string {
                return Str\lowercase($method->getName());
            }),
            $methods
        );
    }
}
