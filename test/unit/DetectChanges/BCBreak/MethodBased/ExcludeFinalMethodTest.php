<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\ExcludeFinalMethod;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodBased;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function assert;

#[CoversClass(ExcludeFinalMethod::class)]
final class ExcludeFinalMethodTest extends TestCase
{
    public function testNonFinalMethodsAreNotExcluded(): void
    {
        $fromMethod = self::method(
            <<<'PHP'
<?php

class A {
    public function method() {}
}
PHP,
        );
        $toMethod   = self::method(
            <<<'PHP'
<?php

class A {
    final public function method() {}
}
PHP,
        );

        $check = $this->createMock(MethodBased::class);
        $check->expects(self::once())
              ->method('__invoke')
              ->with($fromMethod, $toMethod)
              ->willReturn(Changes::fromList(Change::changed('foo', true)));

        self::assertEquals(
            Changes::fromList(Change::changed('foo', true)),
            (new ExcludeFinalMethod($check))($fromMethod, $toMethod),
        );
    }

    public function testFinalMethodsAreExcluded(): void
    {
        $fromMethod = self::method(
            <<<'PHP'
<?php

class A {
    final public function method() {}
}
PHP,
        );
        $toMethod   = self::method(
            <<<'PHP'
<?php

class A {
    public function method() {}
}
PHP,
        );

        $check = $this->createMock(MethodBased::class);
        $check->expects(self::never())
              ->method('__invoke');

        self::assertEquals(
            Changes::empty(),
            (new ExcludeFinalMethod($check))($fromMethod, $toMethod),
        );
    }

    /** @param non-empty-string $source */
    private static function method(string $source): ReflectionMethod
    {
        $method = (new DefaultReflector(new StringSourceLocator(
            $source,
            (new BetterReflection())->astLocator(),
        )))
            ->reflectClass('A')
            ->getMethod('method');

        assert($method !== null);

        return $method;
    }
}
