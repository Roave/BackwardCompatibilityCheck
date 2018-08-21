<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\LocateDependencies;

use Composer\Installer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\LocateDependencies\LocateDependenciesViaComposer;
use Roave\BackwardCompatibility\SourceLocator\StaticClassMapSourceLocator;
use Roave\BackwardCompatibility\SourceLocator\StubClassSourceLocator;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use function getcwd;
use function realpath;

/**
 * @covers \Roave\BackwardCompatibility\LocateDependencies\LocateDependenciesViaComposer
 */
final class LocateDependenciesViaComposerTest extends TestCase
{
    /** @var string */
    private $originalCwd;

    /** @var callable */
    private $makeInstaller;

    /** @var Installer|MockObject */
    private $composerInstaller;

    /** @var string|null */
    private $expectedInstallatonPath;

    /** @var Locator */
    private $astLocator;

    /** @var LocateDependenciesViaComposer */
    private $locateDependencies;

    protected function setUp() : void
    {
        parent::setUp();

        $originalCwd = getcwd();

        self::assertInternalType('string', $originalCwd);

        $this->originalCwd       = $originalCwd;
        $this->composerInstaller = $this->createMock(Installer::class);
        $this->astLocator        = (new BetterReflection())->astLocator();
        $this->makeInstaller     = function (string $installationPath) : Installer {
            self::assertSame($this->expectedInstallatonPath, $installationPath);

            return $this->composerInstaller;
        };

        $this
            ->composerInstaller
            ->expects(self::atLeastOnce())
            ->method('setDevMode')
            ->with(false);
        $this
            ->composerInstaller
            ->expects(self::atLeastOnce())
            ->method('setDumpAutoloader')
            ->with(true);
        $this
            ->composerInstaller
            ->expects(self::atLeastOnce())
            ->method('setRunScripts')
            ->with(false);
        $this
            ->composerInstaller
            ->expects(self::atLeastOnce())
            ->method('setOptimizeAutoloader')
            ->with(true);
        $this
            ->composerInstaller
            ->expects(self::atLeastOnce())
            ->method('setClassMapAuthoritative')
            ->with(true);
        $this
            ->composerInstaller
            ->expects(self::atLeastOnce())
            ->method('setIgnorePlatformRequirements')
            ->with(true);

        $this->locateDependencies = new LocateDependenciesViaComposer($this->makeInstaller, $this->astLocator);
    }

    protected function tearDown() : void
    {
        self::assertSame($this->originalCwd, getcwd());

        parent::tearDown();
    }

    public function testWillLocateDependencies() : void
    {
        $this->expectedInstallatonPath = $this->realpath(__DIR__ . '/../../asset/composer-installation-structure');

        $this
            ->composerInstaller
            ->expects(self::once())
            ->method('run')
            ->willReturnCallback(function () : void {
                self::assertSame($this->expectedInstallatonPath, getcwd());
            });

        $locator = $this
            ->locateDependencies
            ->__invoke($this->expectedInstallatonPath);

        self::assertInstanceOf(AggregateSourceLocator::class, $locator);

        $reflectionLocators = new \ReflectionProperty(AggregateSourceLocator::class, 'sourceLocators');

        $reflectionLocators->setAccessible(true);

        $locators = $reflectionLocators->getValue($locator);

        self::assertCount(4, $locators);
        self::assertEquals(
            new StaticClassMapSourceLocator(
                [
                    'A\\ClassName' => $this->realpath(__DIR__ . '/../../asset/composer-installation-structure/AClassName.php'),
                    'B\\ClassName' => $this->realpath(__DIR__ . '/../../asset/composer-installation-structure/BClassName.php'),
                ],
                $this->astLocator
            ),
            $locators[0]
        );
        self::assertEquals(
            new AggregateSourceLocator([
                new SingleFileSourceLocator(
                    $this->realpath(__DIR__ . '/../../asset/composer-installation-structure/included-file-1.php'),
                    $this->astLocator
                ),
                new SingleFileSourceLocator(
                    $this->realpath(__DIR__ . '/../../asset/composer-installation-structure/included-file-2.php'),
                    $this->astLocator
                ),
            ]),
            $locators[1]
        );
        self::assertInstanceOf(PhpInternalSourceLocator::class, $locators[2]);
        self::assertInstanceOf(StubClassSourceLocator::class, $locators[3]);
    }

    public function testWillLocateDependenciesEvenWithoutAutoloadFiles() : void
    {
        $this->expectedInstallatonPath = $this->realpath(__DIR__ . '/../../asset/composer-installation-structure-without-autoload-files');

        $this
            ->composerInstaller
            ->expects(self::once())
            ->method('run')
            ->willReturnCallback(function () : void {
                self::assertSame($this->expectedInstallatonPath, getcwd());
            });

        $locator = $this
            ->locateDependencies
            ->__invoke($this->expectedInstallatonPath);

        self::assertInstanceOf(AggregateSourceLocator::class, $locator);

        $reflectionLocators = new \ReflectionProperty(AggregateSourceLocator::class, 'sourceLocators');

        $reflectionLocators->setAccessible(true);

        $locators = $reflectionLocators->getValue($locator);

        self::assertCount(4, $locators);
        self::assertEquals(
            new StaticClassMapSourceLocator(
                [
                    'A\\ClassName' => $this->realpath(__DIR__ . '/../../asset/composer-installation-structure-without-autoload-files/AClassName.php'),
                    'B\\ClassName' => $this->realpath(__DIR__ . '/../../asset/composer-installation-structure-without-autoload-files/BClassName.php'),
                ],
                $this->astLocator
            ),
            $locators[0]
        );
        self::assertEquals(new AggregateSourceLocator(), $locators[1]);
        self::assertInstanceOf(PhpInternalSourceLocator::class, $locators[2]);
        self::assertInstanceOf(StubClassSourceLocator::class, $locators[3]);
    }

    private function realpath(string $path) : string
    {
        $realPath = realpath($path);

        self::assertInternalType('string', $realPath);

        return $realPath;
    }
}
