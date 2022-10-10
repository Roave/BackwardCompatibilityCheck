<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\LocateDependencies;

use Composer\Installer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psl\Env;
use Psl\Exception\InvariantViolationException;
use Psl\Filesystem;
use Psl\Type;
use ReflectionProperty;
use Roave\BackwardCompatibility\LocateDependencies\LocateDependenciesViaComposer;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

/** @covers \Roave\BackwardCompatibility\LocateDependencies\LocateDependenciesViaComposer */
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

        $astLocator    = (new BetterReflection())->astLocator();
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

        ($this->locateDependencies)(__DIR__ . '/non-existing', false);
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
            ->method('setPlatformRequirementFilter');

        $this
            ->composerInstaller
            ->expects(self::once())
            ->method('run')
            ->willReturnCallback(function (): int {
                self::assertSame($this->expectedInstallationPath, Env\current_dir());
                
                return 0;
            });

        $locator = ($this->locateDependencies)($this->expectedInstallationPath, false);

        self::assertInstanceOf(AggregateSourceLocator::class, $locator);

        $reflectionLocators = new ReflectionProperty(AggregateSourceLocator::class, 'sourceLocators');

        $reflectionLocators->setAccessible(true);

        $locators = Type\shape([
            0 => Type\instance_of(SourceLocator::class),
            1 => Type\instance_of(SourceLocator::class),
        ])->coerce($reflectionLocators->getValue($locator));

        self::assertCount(2, $locators);
        self::assertInstanceOf(PhpInternalSourceLocator::class, $locators[0]);
    }

    public function testInternalReflectionStubsTakePriorityOverInstalledPolyfills(): void
    {
        $this->expectedInstallationPath = Type\string()
            ->assert(Filesystem\canonicalize(__DIR__ . '/../../asset/composer-installation-with-vendor-overriding-internal-sources'));

        $reflector = new DefaultReflector(($this->locateDependencies)($this->expectedInstallationPath, false));

        self::assertTrue(
            $reflector->reflectClass('Stringable')
                ->isInternal(),
        );
    }

    public function testDevelopmentDependenciesCanBeOptionallyInstalled(): void
    {
        $this->expectedInstallationPath = Type\string()
            ->assert(Filesystem\canonicalize(__DIR__ . '/../../asset/composer-installation-structure'));

        $this
            ->composerInstaller
            ->expects(self::atLeastOnce())
            ->method('setDevMode')
            ->with(true);
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
            ->method('setPlatformRequirementFilter');

        $this
            ->composerInstaller
            ->expects(self::once())
            ->method('run')
            ->willReturnCallback(function (): int {
                self::assertSame($this->expectedInstallationPath, Env\current_dir());
                
                return 0;
            });

        $locator = ($this->locateDependencies)($this->expectedInstallationPath, true);

        self::assertInstanceOf(AggregateSourceLocator::class, $locator);

        $reflectionLocators = new ReflectionProperty(AggregateSourceLocator::class, 'sourceLocators');

        $reflectionLocators->setAccessible(true);

        $locators = Type\shape([
            0 => Type\instance_of(SourceLocator::class),
            1 => Type\instance_of(SourceLocator::class),
        ])->coerce($reflectionLocators->getValue($locator));

        self::assertCount(2, $locators);
        self::assertInstanceOf(PhpInternalSourceLocator::class, $locators[0]);
    }
}
