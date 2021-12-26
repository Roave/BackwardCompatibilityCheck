<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Formatter;

use BadMethodCallException;
use PhpParser\Node\Stmt\Function_;
use PHPUnit\Framework\TestCase;
use Psl\Type;
use Roave\BackwardCompatibility\Formatter\SymbolStartColumn;
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

/**
 * @covers \Roave\BackwardCompatibility\Formatter\SymbolStartColumn
 */
final class SymbolStartColumnTest extends TestCase
{
    public function testCanGetStartColumnForSimpleSymbol(): void
    {
        $reflector = new DefaultReflector(new StringSourceLocator(
            '<?php /* spacing on purpose */ class A {}',
            (new BetterReflection())->astLocator()
        ));

        self::assertSame(32, SymbolStartColumn::get($reflector->reflectClass('A')));
    }

    public function testCannotGetStartColumnWhenAstHasBeenParsedWithoutColumnLocations(): void
    {
        $reflector = new DefaultReflector(new class implements SourceLocator {
            /** Retrieves function `foo`, but without sources (invalid position) */
            public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
            {
                $locatedSource    = new LocatedSource('', null);
                $betterReflection = new BetterReflection();

                $function = Type\shape([
                    0 => Type\object(Function_::class),
                ])->coerce(
                    $betterReflection->phpParser()
                        ->parse('<?php function foo() {}')
                )[0];

                return ReflectionFunction::createFromNode(
                    $betterReflection->reflector(),
                    $function,
                    $locatedSource
                );
            }

            /** {@inheritDoc} */
            public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
            {
                throw new BadMethodCallException('Unused');
            }
        });

        self::assertNull(SymbolStartColumn::get($reflector->reflectFunction('foo')));
    }
}
