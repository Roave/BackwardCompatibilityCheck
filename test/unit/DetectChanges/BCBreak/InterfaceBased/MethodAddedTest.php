<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\MethodAdded;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

final class MethodAddedTest extends TestCase
{
    /**
     * @param string[] $expectedMessages
     *
     * @dataProvider interfacesToBeTested
     */
    public function testDiffs(
        ReflectionClass $fromInterface,
        ReflectionClass $toInterface,
        array $expectedMessages
    ): void {
        $changes = (new MethodAdded())($fromInterface, $toInterface);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /**
     * @return array<string, array<int, ReflectionClass|array<int, string>>>
     * @psalm-return array<string, array{0: ReflectionClass, 1: ReflectionClass, 2: list<string>}>
     */
    public function interfacesToBeTested(): array
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
interface F {
    public function a() {}
    public function c() {}
}
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
interface F {
    public function a() {}
    public function b() {}
    public function c() {}
}
PHP
            ,
            $astLocator
        );

        $fromClassReflector = new DefaultReflector($fromLocator);
        $toClassReflector   = new DefaultReflector($toLocator);

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
            'F' => ['[BC] ADDED: Method b() was added to interface F'],
        ];

        return array_combine(
            array_keys($properties),
            array_map(
                /** @psalm-param list<string> $errorMessages https://github.com/vimeo/psalm/issues/2772 */
                static function (string $className, array $errorMessages) use ($fromClassReflector, $toClassReflector
                ): array {
                    return [
                        $fromClassReflector->reflectClass($className),
                        $toClassReflector->reflectClass($className),
                        $errorMessages,
                    ];
                },
                array_keys($properties),
                $properties
            )
        );
    }
}
