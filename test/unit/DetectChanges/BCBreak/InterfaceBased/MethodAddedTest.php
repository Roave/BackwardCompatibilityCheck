<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\MethodAdded;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

final class MethodAddedTest extends TestCase
{
    /**
     * @dataProvider interfacesToBeTested
     *
     * @param string[] $expectedMessages
     */
    public function testDiffs(
        ReflectionClass $fromInterface,
        ReflectionClass $toInterface,
        array $expectedMessages
    ) : void {
        $changes = (new MethodAdded())
            ->__invoke($fromInterface, $toInterface);

        self::assertSame(
            $expectedMessages,
            array_map(function (Change $change) : string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /** @return (string[]|ReflectionClass)[][] */
    public function interfacesToBeTested() : array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

interface A {}
interface B {
    function removed() {}
}
interface C {
    function kept() {}
}
interface D {
    function casingChanged() {}
}
interface E {}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

interface A {
    function added() {}
}
interface B {}
interface C {
    function kept() {}
}
interface D {
    function casingchanged() {}
}
interface E {
    function added1() {}
    function added2() {}
    function ADDED3() {}
}
PHP
            ,
            $astLocator
        );

        $fromClassReflector = new ClassReflector($fromLocator);
        $toClassReflector   = new ClassReflector($toLocator);

        $properties = [
            'A' => ['[BC] ADDED: Method added() was added to interface A'],
            'B' => [],
            'C' => [],
            'D' => [],
            'E' => [
                '[BC] ADDED: Method added1() was added to interface E',
                '[BC] ADDED: Method added2() was added to interface E',
                '[BC] ADDED: Method ADDED3() was added to interface E',
            ],
        ];

        return array_combine(
            array_keys($properties),
            array_map(
                function (string $className, array $errorMessages) use ($fromClassReflector, $toClassReflector
                ) : array {
                    return [
                        $fromClassReflector->reflect($className),
                        $toClassReflector->reflect($className),
                        $errorMessages,
                    ];
                },
                array_keys($properties),
                $properties
            )
        );
    }
}
