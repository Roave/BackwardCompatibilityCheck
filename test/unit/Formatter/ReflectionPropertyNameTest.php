<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Formatter;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Formatter\ReflectionPropertyName;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use RoaveTest\BackwardCompatibility\TypeRestriction;

use function array_combine;
use function array_keys;
use function array_map;

/**
 * @covers \Roave\BackwardCompatibility\Formatter\ReflectionPropertyName
 */
final class ReflectionPropertyNameTest extends TestCase
{
    /**
     * @dataProvider propertiesToBeTested
     */
    public function testName(ReflectionProperty $property, string $expectedName): void
    {
        self::assertSame($expectedName, (new ReflectionPropertyName())->__invoke($property));
    }

    /**
     * @return array<string, array<int, string|ReflectionProperty>>
     *
     * @psalm-return array<string, array{0: ReflectionProperty, 1: string}>
     */
    public function propertiesToBeTested(): array
    {
        $locator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace {
    trait TestTrait {
        public static $b;
        public $c;
    }
    class A {
        public static $b;
        public $c;
        public $d;
    }
    class B {
        use TestTrait;
        
        public $d;
    }
    class C {
        use TestTrait;
        
        public $c;
    }
    class D extends A {
        public $c;
    }
    class E extends A {
    }
}
namespace N1 {
    class D {
        public static $e;
        public $f;
    }
}
PHP
            ,
            (new BetterReflection())->astLocator()
        );

        $classReflector = new ClassReflector($locator);

        /** @var array<string, ReflectionProperty> $properties */
        $properties = [
            'A::$b'         => $classReflector->reflect('A')->getProperty('b'),
            'A#$c'          => $classReflector->reflect('A')->getProperty('c'),
            'TestTrait::$b' => $classReflector->reflect('TestTrait')->getProperty('b'),
            'TestTrait#$c'  => $classReflector->reflect('TestTrait')->getProperty('c'),
            'B::$b'         => $classReflector->reflect('B')->getProperty('b'),
            'B#$c'          => $classReflector->reflect('B')->getProperty('c'),
            'B#$d'          => $classReflector->reflect('B')->getProperty('d'),
            'C#$c'          => $classReflector->reflect('C')->getProperty('c'),
            'D#$c'          => $classReflector->reflect('D')->getProperty('c'),
            'A#$d'          => $classReflector->reflect('E')->getProperty('d'),
            'N1\D::$e'      => $classReflector->reflect('N1\D')->getProperty('e'),
            'N1\D#$f'       => $classReflector->reflect('N1\D')->getProperty('f'),
        ];

        return array_combine(
            array_keys($properties),
            array_map(
                static function (string $expectedMessage, ReflectionProperty $property): array {
                    return [$property, $expectedMessage];
                },
                array_keys($properties),
                $properties
            )
        );
    }
}
