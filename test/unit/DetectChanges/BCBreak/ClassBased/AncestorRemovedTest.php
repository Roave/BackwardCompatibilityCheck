<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\AncestorRemoved;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\AncestorRemoved
 */
final class AncestorRemovedTest extends TestCase
{
    /**
     * @param string[] $expectedMessages
     *
     * @dataProvider classesToBeTested
     */
    public function testDiffs(
        ReflectionClass $fromClass,
        ReflectionClass $toClass,
        array $expectedMessages
    ): void {
        $changes = (new AncestorRemoved())($fromClass, $toClass);

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
    public function classesToBeTested(): array
    {
        $locator       = (new BetterReflection())->astLocator();
        $fromReflector = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

class A {}
class B extends A {}
class C extends A {}
class D extends C {}
interface IA {}
interface IB extends IA {}
interface IC extends IA {}
interface ID extends IB, IE {}
interface IE {}
interface IG {}
class ClassWithNoAncestors {}
class ClassWithAddedAncestors {}
class ClassWithAddedInterface {}
class ClassWithRemovedAncestor extends A {}
class ClassWithRemovedIndirectAncestor extends B {}
class ClassWithRemovedVeryIndirectAncestor extends D {}
class ClassWithRemovedInterface implements IA {}
class ClassWithRemovedIndirectInterface implements IB {}
class ClassWithRemovedVeryIndirectInterface implements ID {}
class ClassWithInvertedInterfaceNames implements IE, IG {}
PHP
            ,
            $locator
        ));
        $toReflector   = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

class A {}
class B {}
class C {}
class D extends C {}
interface IA {}
interface IB {}
interface IC extends IA {}
interface ID extends IB, IE {}
interface IE {}
interface IG {}
class ClassWithNoAncestors {}
class ClassWithAddedAncestors extends A {}
class ClassWithAddedInterface implements IA {}
class ClassWithRemovedAncestor {}
class ClassWithRemovedIndirectAncestor extends B {}
class ClassWithRemovedVeryIndirectAncestor extends D {}
class ClassWithRemovedInterface {}
class ClassWithRemovedIndirectInterface implements IB {}
class ClassWithRemovedVeryIndirectInterface implements ID {}
class ClassWithInvertedInterfaceNames implements IG, IE {}
PHP
            ,
            $locator
        ));

        $classes = [
            'A' => [],
            'B' => ['[BC] REMOVED: These ancestors of B have been removed: ["A"]'],
            'C' => ['[BC] REMOVED: These ancestors of C have been removed: ["A"]'],
            'D' => ['[BC] REMOVED: These ancestors of D have been removed: ["A"]'],
            'IA' => [],
            'IB' => ['[BC] REMOVED: These ancestors of IB have been removed: ["IA"]'],
            'IC' => [],
            'ID' => ['[BC] REMOVED: These ancestors of ID have been removed: ["IA"]'],
            'IE' => [],
            'IG' => [],
            'ClassWithNoAncestors' => [],
            'ClassWithAddedAncestors' => [],
            'ClassWithAddedInterface' => [],
            'ClassWithRemovedAncestor' => ['[BC] REMOVED: These ancestors of ClassWithRemovedAncestor have been removed: ["A"]'],
            'ClassWithRemovedIndirectAncestor' => ['[BC] REMOVED: These ancestors of ClassWithRemovedIndirectAncestor have been removed: ["A"]'],
            'ClassWithRemovedVeryIndirectAncestor' => ['[BC] REMOVED: These ancestors of ClassWithRemovedVeryIndirectAncestor have been removed: ["A"]'],
            'ClassWithRemovedInterface' => ['[BC] REMOVED: These ancestors of ClassWithRemovedInterface have been removed: ["IA"]'],
            'ClassWithRemovedIndirectInterface' => ['[BC] REMOVED: These ancestors of ClassWithRemovedIndirectInterface have been removed: ["IA"]'],
            'ClassWithRemovedVeryIndirectInterface' => ['[BC] REMOVED: These ancestors of ClassWithRemovedVeryIndirectInterface have been removed: ["IA"]'],
            'ClassWithInvertedInterfaceNames' => [],
        ];

        return array_combine(
            array_keys($classes),
            array_map(
                /** @psalm-param list<string> $errors https://github.com/vimeo/psalm/issues/2772 */
                static function (string $className, array $errors) use ($fromReflector, $toReflector): array {
                    return [
                        $fromReflector->reflectClass($className),
                        $toReflector->reflectClass($className),
                        $errors,
                    ];
                },
                array_keys($classes),
                $classes
            )
        );
    }
}
