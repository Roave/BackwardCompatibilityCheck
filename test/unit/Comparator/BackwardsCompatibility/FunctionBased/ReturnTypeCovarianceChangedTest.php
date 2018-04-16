<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased\ReturnTypeCovarianceChanged;
use Roave\ApiCompare\Comparator\Variance\TypeIsCovariant;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_map;
use function iterator_to_array;

final class ReturnTypeCovarianceChangedTest extends TestCase
{
    /**
     * @dataProvider functionsToBeTested
     *
     * @param string[] $expectedMessages
     */
    public function testDiffs(
        ReflectionFunction $fromFunction,
        ReflectionFunction $toFunction,
        array $expectedMessages
    ) : void {
        $changes = (new ReturnTypeCovarianceChanged(new TypeIsCovariant()))
            ->compare($fromFunction, $toFunction);

        self::assertSame(
            $expectedMessages,
            array_map(function (Change $change) : string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /** @return (string[]|ReflectionFunction)[][] */
    public function functionsToBeTested() : array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace {
   class A {}
   function changed() : A {}
   function untouched() : A {}
}

namespace N1 {
   class B {}
   function changed() : int {}
   function untouched() : int {}
}

namespace N2 {
   function changed() : int {}
   function untouched() {}
}

namespace N3 {
   function changed() : int {}
   function untouched() : int {}
}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace {
   class A {}
   function changed() : \N1\B {}
   function untouched() : A {}
}

namespace N1 {
   class B {}
   function changed() : float {}
   function untouched() : int {}
}

namespace N2 {
   function changed() : ?int {}
   function untouched() {}
}

namespace N3 {
   function changed() {}
   function untouched() : int {}
}
PHP
            ,
            $astLocator
        );

        $fromReflector = new FunctionReflector($fromLocator, new ClassReflector($fromLocator));
        $toReflector   = new FunctionReflector($toLocator, new ClassReflector($toLocator));

        $functions = [
            'changed'      => [
                '[BC] CHANGED: The return type of function changed changed from A to the non-covariant N1\B',
            ],
            'untouched'    => [],
            'N1\changed'   => [
                '[BC] CHANGED: The return type of function N1\changed changed from int to the non-covariant float',
            ],
            'N1\untouched' => [],
            'N2\changed'   => [
                '[BC] CHANGED: The return type of function N2\changed changed from int to the non-covariant ?int',
            ],
            'N2\untouched' => [],
            'N3\changed'   => [
                '[BC] CHANGED: The return type of function N3\changed changed from int to the non-covariant no type',
            ],
            'N3\untouched' => [],
        ];

        return array_map(
            function (string $function, array $errorMessages) use ($fromReflector, $toReflector) : array {
                return [
                    $fromReflector->reflect($function),
                    $toReflector->reflect($function),
                    $errorMessages,
                ];
            },
            array_keys($functions),
            $functions
        );
    }
}
