<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Formatter;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Formatter\ReflectionPropertyName;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
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
    public function testName(ReflectionProperty $property, string $expectedName) : void
    {
        self::assertSame($expectedName, (new ReflectionPropertyName())->__invoke($property));
    }

    /** @return (string|ReflectionProperty)[][] */
    public function propertiesToBeTested() : array
    {
        $locator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace {
    class A {
        public static $b;
        public $c;
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

        $properties = [
            'A::$b'    => $classReflector->reflect('A')->getProperty('b'),
            'A#$c'     => $classReflector->reflect('A')->getProperty('c'),
            'N1\D::$e' => $classReflector->reflect('N1\D')->getProperty('e'),
            'N1\D#$f'  => $classReflector->reflect('N1\D')->getProperty('f'),
        ];

        return array_combine(
            array_keys($properties),
            array_map(
                static function (string $expectedMessage, ReflectionProperty $property) : array {
                    return [$property, $expectedMessage];
                },
                array_keys($properties),
                $properties
            )
        );
    }
}
