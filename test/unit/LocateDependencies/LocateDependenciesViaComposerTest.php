<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\LocateDependencies;

use Composer\Installer;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Roave\BackwardCompatibility\LocateDependencies\LocateDependenciesViaComposer;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;

use function Safe\getcwd;
use function Safe\realpath;

/**
 * @covers \Roave\BackwardCompatibility\LocateDependencies\LocateDependenciesViaComposer
 */
final class LocateDependenciesViaComposerTest extends TestCase
{
    private string $originalCwd;

    /**
     * @var callable
     * @psalm-var callable(string) : Installer
     */
    private $makeInstaller;

    /** @var Installer&MockObject */
    private Installer $composerInstaller;

    private ?string $expectedInstallatonPath = null;

    private Locator $astLocator;

    private LocateDependenciesViaComposer $locateDependencies;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalCwd       = getcwd();
        $this->composerInstaller = $this->createMock(Installer::class);
        $this->astLocator        = (new BetterReflection())->astLocator();
        $this->makeInstaller     = function (string $installationPath): Installer {
            self::assertSame($this->expectedInstallatonPath, $installationPath);

            return $this->composerInstaller;
        };

        $this->locateDependencies = new LocateDependenciesViaComposer($this->makeInstaller, $this->astLocator);
    }

    protected function tearDown(): void
    {
        self::assertSame($this->originalCwd, getcwd());

        parent::tearDown();
    }

    public function testWillNotLocateDependenciesForANonExistingPath(): void
    {
        $this
            ->composerInstaller
            ->expects(self::never())
            ->method('run');

        $this->expectException(InvalidArgumentException::class);

        $this
            ->locateDependencies
            ->__invoke(__DIR__ . '/non-existing');
    }

    public function testWillLocateDependencies(): void
    {
        $this->expectedInstallatonPath = realpath(__DIR__ . '/../../asset/composer-installation-structure');

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
                self::assertSame($this->expectedInstallatonPath, getcwd());
            });

        $locator = $this
            ->locateDependencies
            ->__invoke($this->expectedInstallatonPath);

        self::assertInstanceOf(AggregateSourceLocator::class, $locator);

        $reflectionLocators = new ReflectionProperty(AggregateSourceLocator::class, 'sourceLocators');

        $reflectionLocators->setAccessible(true);

        $locators = $reflectionLocators->getValue($locator);

        self::assertCount(2, $locators);
        self::assertInstanceOf(PhpInternalSourceLocator::class, $locators[1]);
    }
}
