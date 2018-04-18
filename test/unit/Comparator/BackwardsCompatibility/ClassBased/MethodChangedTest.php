<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\MethodChanged;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\MethodBased\MethodBased;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function strtolower;

/**
 * @covers \Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\MethodChanged
 */
final class MethodChangedTest extends TestCase
{
    public function testWillDetectChangesInMethods() : void
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public function a() {}
    protected function b() {}
    private function c() {}
    private static function d() {}
    public function G() {}
}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    protected function b() {}
    private static function d() {}
    public function e() {}
    public function f() {}
    public function g() {}
}
PHP
            ,
            $astLocator
        );

        $comparator = $this->createMock(MethodBased::class);

        $comparator
            ->expects(self::exactly(3))
            ->method('compare')
            ->willReturnCallback(function (ReflectionMethod $from, ReflectionMethod $to) : Changes {
                $methodName = $from->getName();

                self::assertSame(strtolower($methodName), strtolower($to->getName()));

                return Changes::fromArray([Change::added($methodName, true)]);
            });

        self::assertEquals(
            Changes::fromArray([
                Change::added('b', true),
                Change::added('d', true),
                Change::added('G', true),
            ]),
            (new MethodChanged($comparator))->compare(
                (new ClassReflector($fromLocator))->reflect('TheClass'),
                (new ClassReflector($toLocator))->reflect('TheClass')
            )
        );
    }
}
