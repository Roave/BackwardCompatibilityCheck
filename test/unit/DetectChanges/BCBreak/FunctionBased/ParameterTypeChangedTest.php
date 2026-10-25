<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ParameterTypeChanged;
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

#[CoversClass(ParameterTypeChanged::class)]
final class ParameterTypeChangedTest extends TestCase
{
    /** @param string[] $expectedMessages */
    #[DataProvider('functionsToBeTested')]
    public function testDiffs(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction,
        array $expectedMessages,
    ): void {
        $changes = (new ParameterTypeChanged())($fromFunction, $toFunction);

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
   function changed(int $a, int $b) {}
   function untouched(int $a, int $b) {}
}

namespace N1 {
   class A {}
   function changed(A $a, A $b) {}
   function untouched(A $a, A $b) {}
}

namespace N2 {
   class A {}
   function changed(A $a, A $b) {}
   function untouched(A $a, A $b) {}
}

namespace N3 {
   function changed(?int $a, ?int $b) {}
   function untouched(?int $a, ?int $b) {}
}

namespace N4 {
   class C {
       static function changed1($a, $b) {}
       function changed2($a, $b) {}
   }
}

namespace N5 {
   class C {
       function __construct(int $a) {}
   }

   abstract class D {
       abstract function __construct(int $a);
   }

   interface E {
       function __construct(int $a);
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
   function changed(float $a, float $b) {}
   function untouched(int $a, int $b) {}
}

namespace N1 {
   class A {}
   function changed(\N2\A $a, \N2\A $b) {}
   function untouched(A $a, A $b) {}
}

namespace N2 {
   class A {}
   function changed(\N3\A $b) {}
   function untouched(A $a, A $b) {}
}

namespace N3 {
   class A {}
   function changed(int $d, int $e, int $f) {}
   function untouched(?int $a, ?int $b) {}
}

namespace N4 {
   class C {
       static function changed1(int $a, int $b) {}
       function changed2(int $a, int $b) {}
   }
}

namespace N5 {
   class C {
       function __construct(int|string $a) {}
   }

   abstract class D {
       abstract function __construct(int|string $a);
   }

   interface E {
       function __construct(int|string $a);
   }
}
PHP
            ,
            $astLocator,
        );

        $fromReflector = new DefaultReflector($fromLocator);
        $toReflector   = new DefaultReflector($toLocator);

        $functions = [
            'changed'      => [
                '[BC] CHANGED: The parameter $a of changed() changed from int to float',
                '[BC] CHANGED: The parameter $b of changed() changed from int to float',
            ],
            'untouched'    => [],
            'N1\changed'   => [
                '[BC] CHANGED: The parameter $a of N1\changed() changed from N1\A to N2\A',
                '[BC] CHANGED: The parameter $b of N1\changed() changed from N1\A to N2\A',
            ],
            'N1\untouched' => [],
            'N2\changed'   => ['[BC] CHANGED: The parameter $a of N2\changed() changed from N2\A to N3\A'],
            'N2\untouched' => [],
            'N3\changed'   => [
                '[BC] CHANGED: The parameter $a of N3\changed() changed from int|null to int',
                '[BC] CHANGED: The parameter $b of N3\changed() changed from int|null to int',
            ],
            'N3\untouched' => [],
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
                'N4\C::changed1' => [
                    self::getMethod($fromReflector->reflectClass('N4\C'), 'changed1'),
                    self::getMethod($toReflector->reflectClass('N4\C'), 'changed1'),
                    [
                        '[BC] CHANGED: The parameter $a of N4\C::changed1() changed from no type to int',
                        '[BC] CHANGED: The parameter $b of N4\C::changed1() changed from no type to int',

                    ],
                ],
                'N4\C#changed2'  => [
                    self::getMethod($fromReflector->reflectClass('N4\C'), 'changed2'),
                    self::getMethod($toReflector->reflectClass('N4\C'), 'changed2'),
                    [
                        '[BC] CHANGED: The parameter $a of N4\C#changed2() changed from no type to int',
                        '[BC] CHANGED: The parameter $b of N4\C#changed2() changed from no type to int',
                    ],
                ],
                'N5\C#__construct' => [
                    self::getMethod($fromReflector->reflectClass('N5\C'), '__construct'),
                    self::getMethod($toReflector->reflectClass('N5\C'), '__construct'),
                    [],
                ],
                'N5\D#__construct' => [
                    self::getMethod($fromReflector->reflectClass('N5\D'), '__construct'),
                    self::getMethod($toReflector->reflectClass('N5\D'), '__construct'),
                    ['[BC] CHANGED: The parameter $a of N5\D#__construct() changed from int to int|string'],
                ],
                'N5\E#__construct' => [
                    self::getMethod($fromReflector->reflectClass('N5\E'), '__construct'),
                    self::getMethod($toReflector->reflectClass('N5\E'), '__construct'),
                    ['[BC] CHANGED: The parameter $a of N5\E#__construct() changed from int to int|string'],
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
