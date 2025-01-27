<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ExcludeAnonymousClasses;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function reset;

#[CoversClass(ExcludeAnonymousClasses::class)]
final class ExcludeAnonymousClassesTest extends TestCase
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
            $locator,
        ));
        $fromReflection = $reflector->reflectClass('ANormalClass');
        $toReflection   = $reflector->reflectClass('ANormalClass');
        $changes        = Changes::fromList(
            Change::added('TEST'),
        );

        $check = $this->createMock(ClassBased::class);
        $check->expects(self::once())
            ->method('__invoke')
            ->with($fromReflection, $toReflection)
            ->willReturn($changes);

        self::assertEquals($changes, (new ExcludeAnonymousClasses($check))($fromReflection, $toReflection));
    }

    public function testAnonymousClassesAreExcluded(): void
    {
        $locator                  = (new BetterReflection())->astLocator();
        $reflector                = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

$anonClass = new class {};
PHP
            ,
            $locator,
        ));
        $allClasses               = $reflector->reflectAllClasses();
        $anonymousClassReflection = reset($allClasses);

        self::assertInstanceOf(ReflectionClass::class, $anonymousClassReflection);

        $check = $this->createMock(ClassBased::class);
        $check->expects(self::never())->method('__invoke');

        self::assertEquals(
            0,
            (new ExcludeAnonymousClasses($check))($anonymousClassReflection, $anonymousClassReflection)
                ->count(),
        );
    }
}
