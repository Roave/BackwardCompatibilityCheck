<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use function array_diff_key;
use function array_map;
use function array_values;
use function Safe\array_combine;
use function Safe\sprintf;
use function strtolower;

final class MethodAdded implements InterfaceBased
{
    public function __invoke(ReflectionClass $fromInterface, ReflectionClass $toInterface) : Changes
    {
        $fromMethods = $this->methods($fromInterface);
        $toMethods   = $this->methods($toInterface);
        $newMethods  = array_diff_key($toMethods, $fromMethods);

        if (! $newMethods) {
            return Changes::empty();
        }

        return Changes::fromList(...array_values(array_map(static function (ReflectionMethod $method) use (
            $fromInterface
        ) : Change {
            return Change::added(
                sprintf(
                    'Method %s() was added to interface %s',
                    $method->getName(),
                    $fromInterface->getName()
                ),
                true
            );
        }, $newMethods)));
    }

    /** @return ReflectionMethod[] indexed by lowercase method name */
    private function methods(ReflectionClass $interface) : array
    {
        $methods = $interface->getMethods();

        return array_combine(
            array_map(static function (ReflectionMethod $method) : string {
                return strtolower($method->getName());
            }, $methods),
            $methods
        );
    }
}
