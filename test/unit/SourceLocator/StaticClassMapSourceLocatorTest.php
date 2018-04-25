<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\SourceLocator;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\SourceLocator\StaticClassMapSourceLocator;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

/**
 * @covers \Roave\ApiCompare\SourceLocator\StaticClassMapSourceLocator
 */
final class StaticClassMapSourceLocatorTest extends TestCase
{
    /** @var Locator|MockObject */
    private $astLocator;

    /** @var Reflector|MockObject */
    private $reflector;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator = $this->createMock(Locator::class);
        $this->reflector  = $this->createMock(Reflector::class);
    }

    public function rejectsEmptyKeys() : void
    {
        $this->expectException(InvalidArgumentException::class);

        new StaticClassMapSourceLocator(
            ['' => __FILE__],
            $this->astLocator
        );
    }

    public function rejectsNonStringKeys() : void
    {
        $this->expectException(InvalidArgumentException::class);

        new StaticClassMapSourceLocator(
            [__FILE__],
            $this->astLocator
        );
    }

    public function acceptsEmptySet() : void
    {
        $locator = new StaticClassMapSourceLocator([], $this->astLocator);

        self::assertNull($locator->locateIdentifier(
            $this->reflector,
            new Identifier(self::class, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        ));
    }

    public function testWillLocateThisClass() : void
    {
        $locator    = new StaticClassMapSourceLocator([self::class => __FILE__], $this->astLocator);
        $reflection = $this->createMock(Reflection::class);

        $this
            ->astLocator
            ->expects(self::once())
            ->method('findReflection')
            ->with($this->reflector, self::callback(function (LocatedSource $source) : bool {
                self::assertSame(file_get_contents(__FILE__), $source->getSource());
                self::assertSame(__FILE__, $source->getFileName());
                self::assertNull($source->getExtensionName());

                return true;
            }))
            ->willReturn($reflection);

        self::assertSame($reflection, $locator->locateIdentifier(
            $this->reflector,
            new Identifier(self::class, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        ));
    }

    public function testWillNotLocateUnknownClass() : void
    {
        $locator = new StaticClassMapSourceLocator([self::class => __FILE__], $this->astLocator);

        $this
            ->astLocator
            ->expects(self::never())
            ->method('findReflection');

        self::assertNull($locator->locateIdentifier(
            $this->reflector,
            new Identifier('Unknown\\ClassName', new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        ));
    }

    public function testWillNotLocateFunctions() : void
    {
        $locator = new StaticClassMapSourceLocator([self::class => __FILE__], $this->astLocator);

        $this
            ->astLocator
            ->expects(self::never())
            ->method('findReflection');

        self::assertNull($locator->locateIdentifier(
            $this->reflector,
            new Identifier(self::class, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))
        ));
    }
}
