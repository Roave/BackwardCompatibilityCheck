<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ClassBecameInternal;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_map;
use function iterator_to_array;

/** @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ClassBecameInternal */
final class ClassBecameInternalTest extends TestCase
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
        $changes = (new ClassBecameInternal())
            ->__invoke($fromClass, $toClass);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /**
     * @return array<string, array<int, ReflectionClass|array<int, string>>>
     *
     * @psalm-return array<string, array{0: ReflectionClass, 1: ReflectionClass, 2: list<string>}>
     */
    public function classesToBeTested(): array
    {
        $locator       = (new BetterReflection())->astLocator();
        $fromReflector = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

class A {}
class B {}
/** @internal */
class C {}
/** @internal */
class D {}
PHP
            ,
            $locator
        ));
        $toReflector   = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

class A {}
/** @internal */
class B {}
/** @internal */
class C {}
class D {}
PHP
            ,
            $locator
        ));

        return [
            'A' => [
                $fromReflector->reflect('A'),
                $toReflector->reflect('A'),
                [],
            ],
            'B' => [
                $fromReflector->reflect('B'),
                $toReflector->reflect('B'),
                ['[BC] CHANGED: B was marked "@internal"'],
            ],
            'C' => [
                $fromReflector->reflect('C'),
                $toReflector->reflect('C'),
                [],
            ],
            'D' => [
                $fromReflector->reflect('D'),
                $toReflector->reflect('D'),
                [],
            ],
        ];
    }
}
