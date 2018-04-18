<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Formatter;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\ApiCompare\Formatter\ReflectionFunctionAbstractName
 */
final class ReflectionFunctionAbstractNameTest extends TestCase
{
    /**
     * @dataProvider functionsToBeTested
     *
     * @param string[] $expectedMessages
     */
    public function testDiffs(ReflectionFunctionAbstract $function, string $expectedName) : void
    {
        self::assertSame($expectedName, (new ReflectionFunctionAbstractName())->__invoke($function));
    }

    /** @return (string[]|ReflectionFunctionAbstract)[][] */
    public function functionsToBeTested() : array
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

        $classReflector    = new ClassReflector($locator);
        $functionReflector = new FunctionReflector($locator, $classReflector);

        return [
            'a'       => [
                $functionReflector->reflect('a'),
                'a()',
            ],
            'N1\b'    => [
                $functionReflector->reflect('N1\b'),
                'N1\b()',
            ],
            'N2\C::d' => [
                $classReflector->reflect('N2\C')->getMethod('d'),
                'N2\C::d()',
            ],
            'N2\C#e'  => [
                $classReflector->reflect('N2\C')->getMethod('e'),
                'N2\C#e()',
            ],
        ];
    }
}
