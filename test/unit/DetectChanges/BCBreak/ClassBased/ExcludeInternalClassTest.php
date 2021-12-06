<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ExcludeInternalClass;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/** @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ExcludeInternalClass */
final class ExcludeInternalClassTest extends TestCase
{
    public function testNormalClassesAreNotExcluded(): void
    {
        $locator        = (new BetterReflection())->astLocator();
        $reflector      = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

class ANormalClass {}
PHP
            ,
            $locator
        ));
        $fromReflection = $reflector->reflectClass('ANormalClass');
        $toReflection   = $reflector->reflectClass('ANormalClass');

        $check = $this->createMock(ClassBased::class);
        $check->expects(self::once())
              ->method('__invoke')
              ->with($fromReflection, $toReflection)
              ->willReturn(Changes::fromList(Change::removed('foo', true)));

        self::assertEquals(
            Changes::fromList(Change::removed('foo', true)),
            (new ExcludeInternalClass($check))($fromReflection, $toReflection)
        );
    }

    public function testInternalClassesAreExcluded(): void
    {
        $locator    = (new BetterReflection())->astLocator();
        $reflector  = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

/** @internal */
class AnInternalClass {}
PHP
            ,
            $locator
        ));
        $reflection = $reflector->reflectClass('AnInternalClass');

        $check = $this->createMock(ClassBased::class);
        $check->expects(self::never())->method('__invoke');

        self::assertEquals(
            Changes::empty(),
            (new ExcludeInternalClass($check))($reflection, $reflection)
        );
    }
}
