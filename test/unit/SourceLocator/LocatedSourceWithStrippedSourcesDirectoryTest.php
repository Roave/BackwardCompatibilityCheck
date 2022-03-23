<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\SourceLocator;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Roave\BackwardCompatibility\SourceLocator\LocatedSourceWithStrippedSourcesDirectory;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

use function array_combine;
use function array_filter;
use function array_map;

/**
 * @covers \Roave\BackwardCompatibility\SourceLocator\LocatedSourceWithStrippedSourcesDirectory
 */
final class LocatedSourceWithStrippedSourcesDirectoryTest extends TestCase
{
    /** @dataProvider verifiedPaths */
    public function testWillStripPrefixFilePathWhenLocatedSourceInConfiguredPath(
        string $sourcePath,
        string $strippedSourcesPath,
        string $expectedPath
    ): void {
        $source = $this->createMock(LocatedSource::class);

        $source
            ->method('getFileName')
            ->willReturn($sourcePath);

        self::assertSame(
            $expectedPath,
            (new LocatedSourceWithStrippedSourcesDirectory($source, $strippedSourcesPath))
                ->getFileName()
        );
    }
    
    /** @return non-empty-list<array{string, string, string}> */
    public function verifiedPaths(): array
    {
        return [
            ['/foo/bar.php', '/foo', '/bar.php'],
            ['/foo/bar.php', '/foo/', 'bar.php'],
            ['/foo/bar.php', '/baz', '/foo/bar.php'],
            ['/foo/bar.php', '', '/foo/bar.php'],
        ];
    }
    
    public function testWillGetSourcesFromGivenLocatedSource(): void
    {
        self::assertSame(
            'SOURCES!!!',
            (new LocatedSourceWithStrippedSourcesDirectory(
                new LocatedSource('SOURCES!!!', null, null),
                '/some/source/directory'
            ))->getSource()
        );
    }

    public function testWillGetSourceNameFromGivenLocatedSource(): void
    {
        self::assertSame(
            'NAME!!!',
            (new LocatedSourceWithStrippedSourcesDirectory(
                new LocatedSource('', 'NAME!!!', null),
                '/some/source/directory'
            ))->getName()
        );
    }

    public function testWillReportInternalSourceFromGivenLocatedSource(): void
    {
        $nonInternalSource = $this->createMock(LocatedSource::class);
        $internalSource    =  $this->createMock(LocatedSource::class);

        $nonInternalSource
            ->method('isInternal')
            ->willReturn(false);

        $internalSource
            ->method('isInternal')
            ->willReturn(true);
        
        self::assertFalse(
            (new LocatedSourceWithStrippedSourcesDirectory(
                $nonInternalSource,
                '/some/source/directory'
            ))->isInternal()
        );
        self::assertTrue(
            (new LocatedSourceWithStrippedSourcesDirectory(
                $internalSource,
                '/some/source/directory'
            ))->isInternal()
        );
    }

    public function testWillGetExtensionNameFromGivenLocatedSource(): void
    {
        $extensionSource = $this->createMock(LocatedSource::class);
        
        $extensionSource
            ->method('getExtensionName')
            ->willReturn('the-extension');
        
        self::assertSame(
            'the-extension',
            (new LocatedSourceWithStrippedSourcesDirectory($extensionSource, '/some/source/directory'))
                ->getExtensionName()
        );
    }

    public function testWillReportEvaledSourceFromGivenLocatedSource(): void
    {
        $nonEvaledSource = $this->createMock(LocatedSource::class);
        $evaledSource    =  $this->createMock(LocatedSource::class);

        $nonEvaledSource
            ->method('isEvaled')
            ->willReturn(false);

        $evaledSource
            ->method('isEvaled')
            ->willReturn(true);

        self::assertFalse(
            (new LocatedSourceWithStrippedSourcesDirectory(
                $nonEvaledSource,
                '/some/source/directory'
            ))->isEvaled()
        );
        self::assertTrue(
            (new LocatedSourceWithStrippedSourcesDirectory(
                $evaledSource,
                '/some/source/directory'
            ))->isEvaled()
        );
    }

    public function testWillGetAliasNameFromGivenLocatedSource(): void
    {
        $aliasedSource = $this->createMock(LocatedSource::class);

        $aliasedSource
            ->method('getAliasName')
            ->willReturn('the-alias');

        self::assertSame(
            'the-alias',
            (new LocatedSourceWithStrippedSourcesDirectory($aliasedSource, '/some/source/directory'))
                ->getAliasName()
        );
    }

    /**
     * This test makes sure that we didn't forget to override any public API of {@see LocatedSource}
     *
     * @dataProvider methodsDeclaredByLocatedSource
     */
    public function testAllMethodsOfOriginalLocatedSourceAreOverridden(ReflectionMethod $method): void
    {
        self::assertSame(
            LocatedSourceWithStrippedSourcesDirectory::class,
            $method
                ->getDeclaringClass()
                ->getName(),
            'Method is re-declared in the subclass'
        );
    }
    
    /** @return array<string, array{ReflectionMethod}> */
    public function methodsDeclaredByLocatedSource(): array
    {
        $methods = array_filter(
            (new ReflectionClass(LocatedSourceWithStrippedSourcesDirectory::class))
                ->getMethods(),
            static fn (ReflectionMethod $method): bool => $method->isPublic() && ! $method->isStatic()
        );
        
        return array_combine(
            array_map(static fn (ReflectionMethod $method): string => $method->getName(), $methods),
            array_map(static fn (ReflectionMethod $method): array => [$method], $methods),
        );
    }
}
