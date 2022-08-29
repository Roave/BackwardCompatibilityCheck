<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\ExcludeInternalMethod;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodBased;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/** @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\ExcludeInternalMethod */
final class ExcludeInternalMethodTest extends TestCase
{
    public function testNormalMethodsAreNotExcluded(): void
    {
        $method = (new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

class A {
    function method() {}
}
PHP
            ,
            (new BetterReflection())->astLocator(),
        )))
            ->reflectClass('A')
            ->getMethod('method');

        $check = $this->createMock(MethodBased::class);
        $check->expects(self::once())
              ->method('__invoke')
              ->with($method, $method)
              ->willReturn(Changes::fromList(Change::removed('foo', true)));

        self::assertEquals(
            Changes::fromList(Change::removed('foo', true)),
            (new ExcludeInternalMethod($check))($method, $method),
        );
    }

    public function testInternalFunctionsAreExcluded(): void
    {
        $method = (new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

class A {
    /** @internal */
    function method() {}
}
PHP
            ,
            (new BetterReflection())->astLocator(),
        )))
            ->reflectClass('A')
            ->getMethod('method');

        $check = $this->createMock(MethodBased::class);
        $check->expects(self::never())
              ->method('__invoke');

        self::assertEquals(
            Changes::empty(),
            (new ExcludeInternalMethod($check))($method, $method),
        );
    }
}
