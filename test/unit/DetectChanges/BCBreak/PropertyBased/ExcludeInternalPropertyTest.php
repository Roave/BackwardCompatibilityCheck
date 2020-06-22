<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\ExcludeInternalProperty;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyBased;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/** @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\ExcludeInternalProperty */
final class ExcludeInternalPropertyTest extends TestCase
{
    public function testNormalPropertiesAreNotExcluded(): void
    {
        $property = (new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

class A {
    public $property;
}
PHP
            ,
            (new BetterReflection())->astLocator()
        )))
            ->reflect('A')
            ->getProperty('property');

        self::assertNotNull($property);

        $check = $this->createMock(PropertyBased::class);
        $check->expects(self::once())
              ->method('__invoke')
              ->with($property, $property)
              ->willReturn(Changes::fromList(Change::removed('foo', true)));

        self::assertEquals(
            Changes::fromList(Change::removed('foo', true)),
            (new ExcludeInternalProperty($check))
                ->__invoke($property, $property)
        );
    }

    public function testInternalPropertiesAreExcluded(): void
    {
        $property = (new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

class A {
    /** @internal */
    public $property;
}
PHP
            ,
            (new BetterReflection())->astLocator()
        )))
            ->reflect('A')
            ->getProperty('property');

        self::assertNotNull($property);

        $check = $this->createMock(PropertyBased::class);
        $check->expects(self::never())
              ->method('__invoke');

        self::assertEquals(
            Changes::empty(),
            (new ExcludeInternalProperty($check))
                ->__invoke($property, $property)
        );
    }
}
