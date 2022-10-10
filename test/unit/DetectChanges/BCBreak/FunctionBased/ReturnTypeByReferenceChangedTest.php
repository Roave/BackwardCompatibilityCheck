<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ReturnTypeByReferenceChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_combine;
use function array_keys;
use function array_map;
use function array_merge;
use function iterator_to_array;

/** @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ReturnTypeByReferenceChanged */
final class ReturnTypeByReferenceChangedTest extends TestCase
{
    /**
     * @param string[] $expectedMessages
     *
     * @dataProvider functionsToBeTested
     */
    public function testDiffs(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction,
        array $expectedMessages,
    ): void {
        $changes = (new ReturnTypeByReferenceChanged())($fromFunction, $toFunction);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes)),
        );
    }

    /**
     * @return array<string, array{
     *     0: ReflectionMethod|ReflectionFunction,
     *     1: ReflectionMethod|ReflectionFunction,
     *     2: list<string>
     * }>
     */
    public function functionsToBeTested(): array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace {
   function valueToReference() {}
   function & referenceToValue() {}
   function valueToValue() {}
   function & referenceToReference() {}
}

namespace N1 {
   class C {
       static function changed1() {}
       function changed2() {}
   }
}
PHP
            ,
            $astLocator,
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace {
   function & valueToReference() {}
   function referenceToValue() {}
   function valueToValue() {}
   function & referenceToReference() {}
}

namespace N1 {
   class C {
       static function & changed1() {}
       function & changed2() {}
   }
}
PHP
            ,
            $astLocator,
        );

        $fromReflector = new DefaultReflector($fromLocator);
        $toReflector   = new DefaultReflector($toLocator);

        $functions = [
            'valueToReference'      => ['[BC] CHANGED: The return value of valueToReference() changed from by-value to by-reference'],
            'referenceToValue'      => ['[BC] CHANGED: The return value of referenceToValue() changed from by-reference to by-value'],
            'valueToValue'    => [],
            'referenceToReference'    => [],
        ];

        return array_merge(
            array_combine(
                array_keys($functions),
                array_map(
                    static fn (string $function, array $errors): array => [
                        $fromReflector->reflectFunction($function),
                        $toReflector->reflectFunction($function),
                        $errors,
                    ],
                    array_keys($functions),
                    $functions,
                ),
            ),
            [
                'N1\C::changed1' => [
                    $fromReflector->reflectClass('N1\C')->getMethod('changed1'),
                    $toReflector->reflectClass('N1\C')->getMethod('changed1'),
                    ['[BC] CHANGED: The return value of N1\C::changed1() changed from by-value to by-reference'],
                ],
                'N1\C#changed2'  => [
                    $fromReflector->reflectClass('N1\C')->getMethod('changed2'),
                    $toReflector->reflectClass('N1\C')->getMethod('changed2'),
                    ['[BC] CHANGED: The return value of N1\C#changed2() changed from by-value to by-reference'],
                ],
            ],
        );
    }
}
