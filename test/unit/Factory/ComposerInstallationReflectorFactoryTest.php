<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Factory;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Factory\ComposerInstallationReflectorFactory;
use Roave\BackwardCompatibility\LocateSources\LocateSources;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\Factory\ComposerInstallationReflectorFactory
 */
final class ComposerInstallationReflectorFactoryTest extends TestCase
{
    /**
     * Note: this test is quite pointless, it is just in place to verify that there aren't any
     *       silly runtime-related regressions.
     */
    public function testWillInstantiateLocator(): void
    {
        $path          = uniqid('path', true);
        $locateSources = $this->createMock(LocateSources::class);
        $sources       = new StringSourceLocator(
            <<<'PHP'
<?php

/** an example */
class Dummy {}
PHP
            ,
            (new BetterReflection())->astLocator()
        );

        $locateSources
            ->expects(self::atLeastOnce())
            ->method('__invoke')
            ->with($path)
            ->willReturn($sources);

        self::assertSame(
            '/** an example */',
            (new ComposerInstallationReflectorFactory($locateSources))(
                $path,
                $this->createMock(SourceLocator::class)
            )
                ->reflect('Dummy')
                ->getDocComment()
        );
    }
}
