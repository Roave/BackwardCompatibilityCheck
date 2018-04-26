<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\LocateDependencies;

use Composer\Installer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\LocateDependencies\LocateDependenciesViaComposer;
use Roave\ApiCompare\SourceLocator\StaticClassMapSourceLocator;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use function getcwd;
use function realpath;

/**
 * @covers \Roave\ApiCompare\LocateDependencies\LocateDependenciesViaComposer
 */
final class LocateDependenciesViaComposerTest extends TestCase
{
    /** @var string */
    private $originalCwd;

    /** @var Installer|MockObject */
    private $composerInstaller;

    /** @var Locator */
    private $astLocator;

    /** @var LocateDependenciesViaComposer */
    private $locateDependencies;

    protected function setUp() : void
    {
        parent::setUp();

        $this->originalCwd       = getcwd();
        $this->composerInstaller = $this->createMock(Installer::class);
        $this->astLocator        = (new BetterReflection())->astLocator();

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

        $this->locateDependencies = new LocateDependenciesViaComposer($this->composerInstaller, $this->astLocator);
    }

    protected function tearDown() : void
    {
        self::assertSame($this->originalCwd, getcwd());

        parent::tearDown();
    }

    public function testWillLocateDependencies() : void
    {
        $composerInstallationStructure = realpath(__DIR__ . '/../../asset/composer-installation-structure');

        $this
            ->composerInstaller
            ->expects(self::once())
            ->method('run')
            ->willReturnCallback(function () use ($composerInstallationStructure) : void {
                self::assertSame($composerInstallationStructure, getcwd());
            });

        $locator = $this
            ->locateDependencies
            ->__invoke($composerInstallationStructure);

        self::assertInstanceOf(AggregateSourceLocator::class, $locator);

        $reflectionLocators = new \ReflectionProperty(AggregateSourceLocator::class, 'sourceLocators');

        $reflectionLocators->setAccessible(true);

        $locators = $reflectionLocators->getValue($locator);

        self::assertCount(3, $locators);
        self::assertEquals(
            new StaticClassMapSourceLocator(
                [
                    'A\\ClassName' => realpath(__DIR__ . '/../../asset/composer-installation-structure/AClassName.php'),
                    'B\\ClassName' => realpath(__DIR__ . '/../../asset/composer-installation-structure/BClassName.php'),
                ],
                $this->astLocator
            ),
            $locators[0]
        );
        self::assertEquals(
            new AggregateSourceLocator([
                new SingleFileSourceLocator(
                    realpath(__DIR__ . '/../../asset/composer-installation-structure/included-file-1.php'),
                    $this->astLocator
                ),
                new SingleFileSourceLocator(
                    realpath(__DIR__ . '/../../asset/composer-installation-structure/included-file-2.php'),
                    $this->astLocator
                ),
            ]),
            $locators[1]
        );
        self::assertInstanceOf(PhpInternalSourceLocator::class, $locators[2]);
    }
}
