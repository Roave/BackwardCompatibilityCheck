<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Factory;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Factory\ComposerInstallationReflectorFactory;
use Roave\BackwardCompatibility\LocateSources\LocateSources;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

/**
 * @covers \Roave\BackwardCompatibility\Factory\ComposerInstallationReflectorFactory
 */
final class ComposerInstallationReflectorFactoryTest extends TestCase
{
    /**
     * Note: this test is quite pointless, it is just in place to verify that there aren't any
     *       silly runtime-related regressions.
     */
    public function testWillInstantiateLocator() : void
    {
        $path          = uniqid('path', true);
        $locateSources = $this->createMock(LocateSources::class);

        $locateSources
            ->expects(self::atLeastOnce())
            ->method('__invoke')
            ->with($path)
            ->willReturn($this->createMock(SourceLocator::class));

        self::assertInstanceOf(
            ClassReflector::class,
            (new ComposerInstallationReflectorFactory($locateSources))
                ->__invoke($path, $this->createMock(SourceLocator::class))
        );
    }
}
