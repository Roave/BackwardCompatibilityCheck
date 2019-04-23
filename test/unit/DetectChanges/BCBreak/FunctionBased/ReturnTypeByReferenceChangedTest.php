<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ReturnTypeByReferenceChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_map;
use function iterator_to_array;
use function Safe\array_combine;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ReturnTypeByReferenceChanged
 */
final class ReturnTypeByReferenceChangedTest extends TestCase
{
    /**
     * @dataProvider functionsToBeTested
     *
     * @param string[] $expectedMessages
     */
    public function testDiffs(
        ReflectionFunctionAbstract $fromFunction,
        ReflectionFunctionAbstract $toFunction,
        array $expectedMessages
    ) : void {
        $changes = (new ReturnTypeByReferenceChanged())
            ->__invoke($fromFunction, $toFunction);

        self::assertSame(
            $expectedMessages,
            array_map(function (Change $change) : string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /** @return (string[]|ReflectionFunctionAbstract)[][] */
    public function functionsToBeTested() : array
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
            $astLocator
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
            $astLocator
        );

        $fromClassReflector = new ClassReflector($fromLocator);
        $toClassReflector   = new ClassReflector($toLocator);
        $fromReflector      = new FunctionReflector($fromLocator, $fromClassReflector);
        $toReflector        = new FunctionReflector($toLocator, $toClassReflector);

        $functions = [
            'valueToReference'      => [
                '[BC] CHANGED: The return value of valueToReference() changed from by-value to by-reference',
            ],
            'referenceToValue'      => [
                '[BC] CHANGED: The return value of referenceToValue() changed from by-reference to by-value',
            ],
            'valueToValue'    => [],
            'referenceToReference'    => [],
        ];

        return array_merge(
            array_combine(
                array_keys($functions),
                array_map(
                    function (string $function, array $errorMessages) use ($fromReflector, $toReflector) : array {
                        return [
                            $fromReflector->reflect($function),
                            $toReflector->reflect($function),
                            $errorMessages,
                        ];
                    },
                    array_keys($functions),
                    $functions
                )
            ),
            [
                'N1\C::changed1' => [
                    $fromClassReflector->reflect('N1\C')->getMethod('changed1'),
                    $toClassReflector->reflect('N1\C')->getMethod('changed1'),
                    ['[BC] CHANGED: The return value of N1\C::changed1() changed from by-value to by-reference'],
                ],
                'N1\C#changed2'  => [
                    $fromClassReflector->reflect('N1\C')->getMethod('changed2'),
                    $toClassReflector->reflect('N1\C')->getMethod('changed2'),
                    ['[BC] CHANGED: The return value of N1\C#changed2() changed from by-value to by-reference'],
                ],
            ]
        );
    }
}
