<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\SourceLocator;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psl\Exception\InvariantViolationException;
use Psl\File;
use Psl\Type\Exception\CoercionException;
use Roave\BackwardCompatibility\SourceLocator\StaticClassMapSourceLocator;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

/** @covers \Roave\BackwardCompatibility\SourceLocator\StaticClassMapSourceLocator */
final class StaticClassMapSourceLocatorTest extends TestCase
{
    /** @var Locator&MockObject */
    private Locator $astLocator;

    /** @var Reflector&MockObject */
    private Reflector $reflector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->astLocator = $this->createMock(Locator::class);
        $this->reflector  = $this->createMock(Reflector::class);
    }

    public function testRejectsEmptyKeys(): void
    {
        $this->expectException(CoercionException::class);
        $this->expectExceptionMessage('Could not coerce "string" to type "non-empty-string".');

        new StaticClassMapSourceLocator(
            ['' => __FILE__],
            $this->astLocator,
        );
    }

    public function testRejectsNonFileInputs(): void
    {
        $this->expectException(InvariantViolationException::class);

        new StaticClassMapSourceLocator(
            ['foo' => __DIR__],
            $this->astLocator,
        );
    }

    public function testAcceptsEmptySet(): void
    {
        $locator = new StaticClassMapSourceLocator([], $this->astLocator);

        self::assertNull($locator->locateIdentifier(
            $this->reflector,
            new Identifier(self::class, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
        ));
    }

    /**
     * @param non-empty-string $thisClassFilePath
     *
     * @dataProvider thisClassPossiblePaths
     */
    public function testWillLocateThisClass(string $thisClassFilePath): void
    {
        $locator    = new StaticClassMapSourceLocator([self::class => $thisClassFilePath], $this->astLocator);
        $reflection = $this->createMock(Reflection::class);

        $this
            ->astLocator
            ->expects(self::once())
            ->method('findReflection')
            ->with($this->reflector, self::callback(static function (LocatedSource $source): bool {
                self::assertSame(File\read(__FILE__), $source->getSource());
                self::assertSame(__FILE__, $source->getFileName());
                self::assertSame(self::class, $source->getName());
                self::assertNull($source->getExtensionName());

                return true;
            }))
            ->willReturn($reflection);

        self::assertSame($reflection, $locator->locateIdentifier(
            $this->reflector,
            new Identifier(self::class, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
        ));
    }

    /** @return list<array{non-empty-string}> */
    public static function thisClassPossiblePaths(): array
    {
        return [
            [__FILE__],
            [__DIR__ . '/../SourceLocator/StaticClassMapSourceLocatorTest.php'],
        ];
    }

    public function testWillNotLocateUnknownClass(): void
    {
        $locator = new StaticClassMapSourceLocator([self::class => __FILE__], $this->astLocator);

        $this
            ->astLocator
            ->expects(self::never())
            ->method('findReflection');

        self::assertNull($locator->locateIdentifier(
            $this->reflector,
            new Identifier('Unknown\\ClassName', new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
        ));
    }

    public function testWillNotLocateFunctions(): void
    {
        $locator = new StaticClassMapSourceLocator([self::class => __FILE__], $this->astLocator);

        $this
            ->astLocator
            ->expects(self::never())
            ->method('findReflection');

        self::assertNull($locator->locateIdentifier(
            $this->reflector,
            new Identifier(self::class, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)),
        ));
    }
}
