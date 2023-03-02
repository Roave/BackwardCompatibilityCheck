<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ParameterByReferenceChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_combine;
use function array_keys;
use function array_map;
use function array_merge;
use function assert;
use function iterator_to_array;

/** @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ParameterByReferenceChanged */
final class ParameterByReferenceChangedTest extends TestCase
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
        $changes = (new ParameterByReferenceChanged())($fromFunction, $toFunction);

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
    public static function functionsToBeTested(): array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace {
    function valueToReference($a) {}
    function referenceToValue(& $a) {}
    function valueToValue($a) {}
    function referenceToReference(& $a) {}
    function referenceOnRemovedParameter($a, & $b) {}
    function referenceToValueOnRenamedParameter(& $a, & $b) {}
    function addedParameter(& $a, & $b) {}
}
namespace N1 {
    class C {
        static function changed1($a) {}
        function changed2($a) {}
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
    function valueToReference(& $a) {}
    function referenceToValue($a) {}
    function valueToValue($a) {}
    function referenceToReference(& $a) {}
    function referenceOnRemovedParameter($a) {}
    function referenceToValueOnRenamedParameter(& $b, $a) {}
    function addedParameter(& $a, & $b, $c) {}
}
namespace N1 {
    class C {
        static function changed1(& $a) {}
        function changed2(& $a) {}
    }
}
PHP
            ,
            $astLocator,
        );

        $fromReflector = new DefaultReflector($fromLocator);
        $toReflector   = new DefaultReflector($toLocator);

        $functions = [
            'valueToReference'                   => ['[BC] CHANGED: The parameter $a of valueToReference() changed from by-value to by-reference'],
            'referenceToValue'                   => ['[BC] CHANGED: The parameter $a of referenceToValue() changed from by-reference to by-value'],
            'valueToValue'                       => [],
            'referenceToReference'               => [],
            'referenceOnRemovedParameter'        => [],
            'referenceToValueOnRenamedParameter' => ['[BC] CHANGED: The parameter $b of referenceToValueOnRenamedParameter() changed from by-reference to by-value'],
            'addedParameter'                     => [],
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
                    self::getMethod($fromReflector->reflectClass('N1\C'), 'changed1'),
                    self::getMethod($toReflector->reflectClass('N1\C'), 'changed1'),
                    ['[BC] CHANGED: The parameter $a of N1\C::changed1() changed from by-value to by-reference'],
                ],
                'N1\C#changed2'  => [
                    self::getMethod($fromReflector->reflectClass('N1\C'), 'changed2'),
                    self::getMethod($toReflector->reflectClass('N1\C'), 'changed2'),
                    ['[BC] CHANGED: The parameter $a of N1\C#changed2() changed from by-value to by-reference'],
                ],
            ],
        );
    }

    /** @param non-empty-string $name */
    private static function getMethod(ReflectionClass $class, string $name): ReflectionMethod
    {
        $method = $class->getMethod($name);

        assert($method !== null);

        return $method;
    }
}
