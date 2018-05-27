<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\SourceLocator;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\SourceLocator\StubClassSourceLocator;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\Reflector;

/**
 * @covers \Roave\BackwardCompatibility\SourceLocator\StubClassSourceLocator
 */
final class StubClassSourceLocatorTest extends TestCase
{
    /** @var StubClassSourceLocator */
    private $stubLocator;

    /** @var Reflector */
    private $reflector;

    protected function setUp() : void
    {
        parent::setUp();

        $betterReflection = new BetterReflection();

        $this->stubLocator = new StubClassSourceLocator($betterReflection->astLocator());
        $this->reflector   = $betterReflection->classReflector();
    }

    public function testWillNotRetrieveSymbolsByType() : void
    {
        self::assertEmpty($this->stubLocator->locateIdentifiersByType(
            $this->reflector,
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        ));
    }

    public function testWillNotRetrieveFunctionReflections() : void
    {
        self::assertNull($this->stubLocator->locateIdentifier(
            $this->reflector,
            new Identifier('foo', new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))
        ));
    }

    public function testWillReflectNonNamespacedClass() : void
    {
        /** @var ReflectionClass $class */
        $class = $this->stubLocator->locateIdentifier(
            $this->reflector,
            new Identifier('AClass', new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        );

        self::assertInstanceOf(ReflectionClass::class, $class);

        self::assertSame('AClass', $class->getName());
        self::assertTrue($class->isInterface());
        self::assertFalse($class->inNamespace());
    }

    public function testWillReflectNamespacedClass() : void
    {
        /** @var ReflectionClass $class */
        $class = $this->stubLocator->locateIdentifier(
            $this->reflector,
            new Identifier('Foo\Bar\AClass', new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        );

        self::assertInstanceOf(ReflectionClass::class, $class);

        self::assertSame('Foo\Bar\AClass', $class->getName());
        self::assertTrue($class->isInterface());
        self::assertTrue($class->inNamespace());
    }
}
