<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ExcludeAnonymousClasses;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function reset;

final class ExcludeAnonymousClassesTest extends TestCase
{
    public function testNormalClassesAreNotExcluded() : void
    {
        $locator        = (new BetterReflection())->astLocator();
        $reflector      = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

class ANormalClass {}
PHP
            ,
            $locator
        ));
        $fromReflection = $reflector->reflect('ANormalClass');
        $toReflection   = $reflector->reflect('ANormalClass');

        $check = $this->createMock(ClassBased::class);
        $check->expects(self::once())
            ->method('__invoke')
            ->with($fromReflection, $toReflection)
            ->willReturn(Changes::empty());

        $excluder = new ExcludeAnonymousClasses($check);
        $excluder->__invoke($fromReflection, $toReflection);
    }

    public function testAnonymousClassesAreExcluded() : void
    {
        $locator                  = (new BetterReflection())->astLocator();
        $reflector                = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

$anonClass = new class {};
PHP
            ,
            $locator
        ));
        $allClasses               = $reflector->getAllClasses();
        $anonymousClassReflection = reset($allClasses);

        self::assertInstanceOf(ReflectionClass::class, $anonymousClassReflection);

        $check = $this->createMock(ClassBased::class);
        $check->expects(self::never())->method('__invoke');

        (new ExcludeAnonymousClasses($check))
            ->__invoke($anonymousClassReflection, $anonymousClassReflection);
    }
}
