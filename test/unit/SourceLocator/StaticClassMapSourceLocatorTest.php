<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\SourceLocator;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\SourceLocator\StaticClassMapSourceLocator;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use function Safe\file_get_contents;

/**
 * @covers \Roave\BackwardCompatibility\SourceLocator\StaticClassMapSourceLocator
 */
final class StaticClassMapSourceLocatorTest extends TestCase
{
    /** @var Locator&MockObject */
    private $astLocator;

    /** @var Reflector&MockObject */
    private $reflector;

    protected function setUp() : void
    {
        parent::setUp();

        $this->astLocator = $this->createMock(Locator::class);
        $this->reflector  = $this->createMock(Reflector::class);
    }

    public function testRejectsEmptyKeys() : void
    {
        $this->expectException(InvalidArgumentException::class);

        new StaticClassMapSourceLocator(
            ['' => __FILE__],
            $this->astLocator
        );
    }

    public function testRejectsEmptyStringFiles() : void
    {
        $this->expectException(InvalidArgumentException::class);

        new StaticClassMapSourceLocator(
            ['foo' => ''],
            $this->astLocator
        );
    }

    public function testAcceptsEmptySet() : void
    {
        $locator = new StaticClassMapSourceLocator([], $this->astLocator);

        self::assertNull($locator->locateIdentifier(
            $this->reflector,
            new Identifier(self::class, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        ));
    }

    /**
     * @dataProvider thisClassPossiblePaths
     */
    public function testWillLocateThisClass(string $thisClassFilePath) : void
    {
        $locator    = new StaticClassMapSourceLocator([self::class => $thisClassFilePath], $this->astLocator);
        $reflection = $this->createMock(Reflection::class);

        $this
            ->astLocator
            ->expects(self::once())
            ->method('findReflection')
            ->with($this->reflector, self::callback(static function (LocatedSource $source) : bool {
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

    /**
     * @return array<int, array<int, string>>
     *
     * @psalm-return list<list<string>>
     */
    public static function thisClassPossiblePaths() : array
    {
        return [
            [__FILE__],
            [__DIR__ . '/../SourceLocator/StaticClassMapSourceLocatorTest.php'],
        ];
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
