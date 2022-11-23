<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Formatter;

use BadMethodCallException;
use PhpParser\Node\Stmt\Function_;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Formatter\SymbolStart;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/** @covers \Roave\BackwardCompatibility\Formatter\SymbolStart */
final class SymbolStartTest extends TestCase
{
    public function testCanGetStartColumnForSimpleSymbol(): void
    {
        $reflector = new DefaultReflector(new StringSourceLocator(
            '<?php /* spacing on purpose */ class A {}',
            (new BetterReflection())->astLocator(),
        ));

        self::assertSame(32, SymbolStart::getColumn($reflector->reflectClass('A')));
    }

    public function testCannotGetStartColumnWhenAstHasBeenParsedWithoutColumnLocations(): void
    {
        $reflector = new DefaultReflector(new class implements SourceLocator {
            /** Retrieves function `foo`, but without sources (invalid position) */
            public function locateIdentifier(Reflector $reflector, Identifier $identifier): Reflection|null
            {
                return ReflectionFunction::createFromNode(
                    (new BetterReflection())->reflector(),
                    new Function_('foo'),
                    new LocatedSource('', null),
                );
            }

            /** {@inheritDoc} */
            public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
            {
                throw new BadMethodCallException('Unused');
            }
        });

        self::assertNull(SymbolStart::getColumn($reflector->reflectFunction('foo')));
    }

    public function testCanGetStartLineForSimpleSymbol(): void
    {
        $reflector = new DefaultReflector(new StringSourceLocator(
            '<?php /* spacing on purpose */ class A {}',
            (new BetterReflection())->astLocator(),
        ));

        self::assertSame(1, SymbolStart::getLine($reflector->reflectClass('A')));
    }

    public function testCannotGetStartLineWhenAstHasBeenParsedWithoutLineLocations(): void
    {
        $reflector = new DefaultReflector(new class implements SourceLocator {
            /** Retrieves function `foo`, but without sources (invalid position) */
            public function locateIdentifier(Reflector $reflector, Identifier $identifier): Reflection|null
            {
                return ReflectionFunction::createFromNode(
                    (new BetterReflection())->reflector(),
                    new Function_('foo'),
                    new LocatedSource('', null),
                );
            }

            /** {@inheritDoc} */
            public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
            {
                throw new BadMethodCallException('Unused');
            }
        });

        self::assertNull(SymbolStart::getLine($reflector->reflectFunction('foo')));
    }
}
