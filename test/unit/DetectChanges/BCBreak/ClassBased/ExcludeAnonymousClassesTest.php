<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ExcludeAnonymousClasses;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

final class ExcludeAnonymousClassesTest extends TestCase
{
    public function testNormalClassesAreNotExcluded() : void
    {
        $locator       = (new BetterReflection())->astLocator();
        $reflector = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

class ANormalClass {}
PHP
            ,
            $locator
        ));
        $fromReflection = $reflector->reflect('ANormalClass');
        $toReflection = $reflector->reflect('ANormalClass');

        /** @var ClassBased|MockObject $check */
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
        $locator       = (new BetterReflection())->astLocator();
        $reflector = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

$anonClass = new class {};
PHP
            ,
            $locator
        ));
        $allClasses = $reflector->getAllClasses();
        $anonymousClassReflection = reset($allClasses);

        /** @var ClassBased|MockObject $check */
        $check = $this->createMock(ClassBased::class);
        $check->expects(self::never())->method('__invoke');

        $excluder = new ExcludeAnonymousClasses($check);
        $excluder->__invoke($anonymousClassReflection, $anonymousClassReflection);
    }
}
