<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\SourceLocator;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Roave\BackwardCompatibility\SourceLocator\LocatedSourceWithStrippedSourcesDirectory;
use Roave\BackwardCompatibility\SourceLocator\ReplaceSourcePathOfLocatedSources;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Reflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

use function array_combine;
use function array_filter;
use function array_map;

/**
 * @covers \Roave\BackwardCompatibility\SourceLocator\ReplaceSourcePathOfLocatedSources
 */
final class ReplaceSourcePathOfLocatedSourcesTest extends TestCase
{
    public function testWillWrapFoundReflection(): void
    {
        $reflection = $this->createMock(Reflection::class);
        $next       = $this->createMock(Locator::class);
        $reflector  = $this->createMock(Reflector::class);
        $source     = $this->createMock(LocatedSource::class);
        $identifier = new Identifier('find-me', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));
        
        $next->expects(self::once())
            ->method('findReflection')
            ->with(
                $reflector,
                self::equalTo(new LocatedSourceWithStrippedSourcesDirectory($source, '/foo')),
                $identifier
            )
            ->willReturn($reflection);
        
        self::assertSame(
            $reflection,
            (new ReplaceSourcePathOfLocatedSources($next, '/foo'))
                ->findReflection($reflector, $source, $identifier)
        );
    }

    public function testWillWrapFoundReflectionsOfType(): void
    {
        $reflection     = $this->createMock(Reflection::class);
        $next           = $this->createMock(Locator::class);
        $reflector      = $this->createMock(Reflector::class);
        $source         = $this->createMock(LocatedSource::class);
        $identifierType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);

        $next->expects(self::once())
            ->method('findReflectionsOfType')
            ->with(
                $reflector,
                self::equalTo(new LocatedSourceWithStrippedSourcesDirectory($source, '/foo')),
                $identifierType
            )
            ->willReturn([$reflection]);

        self::assertSame(
            [$reflection],
            (new ReplaceSourcePathOfLocatedSources($next, '/foo'))
                ->findReflectionsOfType($reflector, $source, $identifierType)
        );
    }

    /**
     * This test makes sure that we didn't forget to override any public API of {@see ReplaceSourcePathOfLocatedSources}
     *
     * @dataProvider methodsDeclaredByReplaceSourcePathOfLocatedSources
     */
    public function testAllMethodsOfBaseClassAreOverridden(ReflectionMethod $method): void
    {
        self::assertSame(
            ReplaceSourcePathOfLocatedSources::class,
            $method
                ->getDeclaringClass()
                ->getName(),
            'Method is re-declared in the subclass'
        );
    }

    /** @return array<string, array{ReflectionMethod}> */
    public function methodsDeclaredByReplaceSourcePathOfLocatedSources(): array
    {
        $methods = array_filter(
            (new ReflectionClass(ReplaceSourcePathOfLocatedSources::class))
                ->getMethods(),
            static fn (ReflectionMethod $method): bool => $method->isPublic() && ! $method->isStatic()
        );

        return array_combine(
            array_map(static fn (ReflectionMethod $method): string => $method->getName(), $methods),
            array_map(static fn (ReflectionMethod $method): array => [$method], $methods),
        );
    }
}
