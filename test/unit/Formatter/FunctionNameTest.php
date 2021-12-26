<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Formatter;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Formatter\FunctionName;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BackwardCompatibility\Formatter\FunctionName
 */
final class FunctionNameTest extends TestCase
{
    /**
     * @dataProvider functionsToBeTested
     */
    public function testName(ReflectionFunction|ReflectionMethod $function, string $expectedName): void
    {
        self::assertSame($expectedName, (new FunctionName())($function));
    }

    /**
     * @return array<string, array{
     *     0: ReflectionFunction|ReflectionMethod,
     *     1: string
     * }>
     */
    public function functionsToBeTested(): array
    {
        $locator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace {
   function a() {}
}

namespace N1 {
    function b() {}
}

namespace N2 {
   class C {
       static function d() {}
       function e() {}
   }
}
PHP
            ,
            (new BetterReflection())->astLocator()
        );

        $reflector = new DefaultReflector($locator);

        return [
            'a'       => [
                $reflector->reflectFunction('a'),
                'a()',
            ],
            'N1\b'    => [
                $reflector->reflectFunction('N1\b'),
                'N1\b()',
            ],
            'N2\C::d' => [
                $reflector->reflectClass('N2\C')->getMethod('d'),
                'N2\C::d()',
            ],
            'N2\C#e'  => [
                $reflector->reflectClass('N2\C')->getMethod('e'),
                'N2\C#e()',
            ],
        ];
    }
}
