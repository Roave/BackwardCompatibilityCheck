<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\RequiredParameterAmountIncreased;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_combine;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\RequiredParameterAmountIncreased
 */
final class RequiredParameterAmountIncreasedTest extends TestCase
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
        $changes = (new RequiredParameterAmountIncreased())($fromFunction, $toFunction);

        self::assertSame(
            $expectedMessages,
            array_map(function (Change $change) : string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /**
     * @return array<string, array<int, ReflectionFunctionAbstract|array<int, string>>>
     *
     * @psalm-return array<string, array{0: ReflectionFunctionAbstract, 1: ReflectionFunctionAbstract, 2: list<string>}>
     */
    public function functionsToBeTested() : array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace {
   function parametersIncreased($a, $b, $c) {}
   function parametersReduced($a, $b, $c) {}
   function parameterNamesChanged($a, $b, $c) {}
   function optionalParameterAdded($a, $b, $c) {}
   function noParametersToOneParameter() {}
   function variadicParameterAdded($a, $b) {}
   function variadicParameterMoved($a, ...$b) {}
   function optionalParameterAddedInBetween($a, $b, $c) {}
   function parameterMadeOptionalMidSignature($a, $b, $c) {}
   function untouched($a, $b, $c) {}
}

namespace N1 {
   class C {
       static function changed1($a, $b, $c) {}
       function changed2($a, $b, $c) {}
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
   function parametersIncreased($a, $b, $c, $d) {}
   function parametersReduced($a, $b) {}
   function parameterNamesChanged($d, $e, $f) {}
   function optionalParameterAdded($a, $b, $c, $d = null) {}
   function noParametersToOneParameter($a) {}
   function variadicParameterAdded($a, $b, ...$c) {}
   function variadicParameterMoved($a, $b, ...$b) {}
   function optionalParameterAddedInBetween($a, $b = null, $c, $d) {}
   function parameterMadeOptionalMidSignature($a, $b = null, $c) {}
   function untouched($a, $b, $c) {}
}

namespace N1 {
   class C {
       static function changed1($a, $b, $c, $d) {}
       function changed2($a, $b, $c, $d) {}
   }
}
PHP
            ,
            $astLocator
        );

        $fromReflector      = new DefaultReflector($fromLocator);
        $toReflector        = new DefaultReflector($toLocator);

        $functions = [
            'parametersIncreased'               => ['[BC] CHANGED: The number of required arguments for parametersIncreased() increased from 3 to 4'],
            'parametersReduced'                 => [],
            'parameterNamesChanged'             => [],
            'optionalParameterAdded'            => [],
            'noParametersToOneParameter'        => ['[BC] CHANGED: The number of required arguments for noParametersToOneParameter() increased from 0 to 1'],
            'variadicParameterAdded'            => [],
            'variadicParameterMoved'            => ['[BC] CHANGED: The number of required arguments for variadicParameterMoved() increased from 1 to 2'],
            'optionalParameterAddedInBetween'   => ['[BC] CHANGED: The number of required arguments for optionalParameterAddedInBetween() increased from 3 to 4'],
            'parameterMadeOptionalMidSignature' => [],
            'untouched'                         => [],
        ];

        return array_merge(
            array_combine(
                array_keys($functions),
                array_map(
                    /** @psalm-param list<string> $errorMessages https://github.com/vimeo/psalm/issues/2772 */
                    function (string $function, array $errorMessages) use ($fromReflector, $toReflector) : array {
                        return [
                            $fromReflector->reflectFunction($function),
                            $toReflector->reflectFunction($function),
                            $errorMessages,
                        ];
                    },
                    array_keys($functions),
                    $functions
                )
            ),
            [
                'N1\C::changed1' => [
                    $fromReflector->reflectClass('N1\C')->getMethod('changed1'),
                    $toReflector->reflectClass('N1\C')->getMethod('changed1'),
                    [
                        '[BC] CHANGED: The number of required arguments for N1\C::changed1() increased from 3 to 4',
                    ],
                ],
                'N1\C#changed2'  => [
                    $fromReflector->reflectClass('N1\C')->getMethod('changed2'),
                    $toReflector->reflectClass('N1\C')->getMethod('changed2'),
                    [
                        '[BC] CHANGED: The number of required arguments for N1\C#changed2() increased from 3 to 4',
                    ],
                ],
            ]
        );
    }
}
