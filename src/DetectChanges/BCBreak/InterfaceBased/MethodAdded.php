<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use Psl\Dict;
use Psl\Str;
use Psl\Vec;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;

final class MethodAdded implements InterfaceBased
{
    public function __invoke(ReflectionClass $fromInterface, ReflectionClass $toInterface): Changes
    {
        $fromMethods = $this->methods($fromInterface);
        $toMethods   = $this->methods($toInterface);
        $newMethods  = Dict\diff_by_key($toMethods, $fromMethods);

        if (! $newMethods) {
            return Changes::empty();
        }

        return Changes::fromList(...Vec\map($newMethods, static function (ReflectionMethod $method) use (
            $fromInterface
        ): Change {
            return Change::added(
                Str\format(
                    'Method %s() was added to interface %s',
                    $method->getName(),
                    $fromInterface->getName()
                )
            );
        }));
    }

    /** @return ReflectionMethod[] indexed by lowercase method name */
    private function methods(ReflectionClass $interface): array
    {
        $methods = $interface->getMethods();

        return Dict\associate(
            Vec\map($methods, static function (ReflectionMethod $method): string {
                return Str\lowercase($method->getName());
            }),
            $methods
        );
    }
}
