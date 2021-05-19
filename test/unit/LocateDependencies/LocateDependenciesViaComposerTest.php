<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\LocateDependencies;

use Composer\Installer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psl\Exception\InvariantViolationException;
use ReflectionProperty;
use Roave\BackwardCompatibility\LocateDependencies\LocateDependenciesViaComposer;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Psl\Env;
use Psl\Type;
use Psl\Filesystem;

/**
 * @covers \Roave\BackwardCompatibility\LocateDependencies\LocateDependenciesViaComposer
 */
final class LocateDependenciesViaComposerTest extends TestCase
{
    private string $originalCwd;

    /** @var Installer&MockObject */
    private Installer $composerInstaller;

    private string $expectedInstallationPath;

    private LocateDependenciesViaComposer $locateDependencies;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalCwd       = Env\current_dir();
        $this->composerInstaller = $this->createMock(Installer::class);

        $astLocator = (new BetterReflection())->astLocator();
        $makeInstaller = function (string $installationPath): Installer {
            self::assertSame($this->expectedInstallationPath, $installationPath);

            return $this->composerInstaller;
        };

        $this->locateDependencies = new LocateDependenciesViaComposer($makeInstaller, $astLocator);
    }

    protected function tearDown(): void
    {
        self::assertSame($this->originalCwd, Env\current_dir());

        parent::tearDown();
    }

    public function testWillNotLocateDependenciesForANonExistingPath(): void
    {
        $this
            ->composerInstaller
            ->expects(self::never())
            ->method('run');

        $this->expectException(InvariantViolationException::class);
        $this->expectExceptionMessage('Could not locate composer.json within installation path.');

        $this
            ->locateDependencies
            ->__invoke(__DIR__ . '/non-existing');
    }

    public function testWillLocateDependencies(): void
    {
        $this->expectedInstallationPath = Type\string()
            ->assert(Filesystem\canonicalize(__DIR__ . '/../../asset/composer-installation-structure'));

        $this
            ->composerInstaller
            ->expects(self::atLeastOnce())
            ->method('setDevMode')
            ->with(false);
        $this
            ->composerInstaller
            ->expects(self::atLeastOnce())
            ->method('setDumpAutoloader')
            ->with(false);
        $this
            ->composerInstaller
            ->expects(self::atLeastOnce())
            ->method('setRunScripts')
            ->with(false);
        $this
            ->composerInstaller
            ->expects(self::atLeastOnce())
            ->method('setIgnorePlatformRequirements')
            ->with(true);

        $this
            ->composerInstaller
            ->expects(self::once())
            ->method('run')
            ->willReturnCallback(function (): void {
                self::assertSame($this->expectedInstallationPath, Env\current_dir());
            });

        $locator = $this
            ->locateDependencies
            ->__invoke($this->expectedInstallationPath);

        self::assertInstanceOf(AggregateSourceLocator::class, $locator);

        $reflectionLocators = new ReflectionProperty(AggregateSourceLocator::class, 'sourceLocators');

        $reflectionLocators->setAccessible(true);

        $locators = $reflectionLocators->getValue($locator);

        self::assertCount(2, $locators);
        self::assertInstanceOf(PhpInternalSourceLocator::class, $locators[1]);
    }
}
