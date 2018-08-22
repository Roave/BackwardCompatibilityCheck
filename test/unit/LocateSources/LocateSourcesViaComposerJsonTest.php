<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\LocateSources;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\LocateSources\LocateSourcesViaComposerJson;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

/**
 * @covers \Roave\BackwardCompatibility\LocateSources\LocateSourcesViaComposerJson
 */
final class LocateSourcesViaComposerJsonTest extends TestCase
{
    /** @var Locator|null */
    private static $astLocator;

    /** @var LocateSourcesViaComposerJson */
    private $locateSources;

    protected function setUp()
    {
        parent::setUp();

        $this->locateSources = new LocateSourcesViaComposerJson($this->astLocator());
    }

    /** @dataProvider locatedSourcesExamples */
    public function testExpectedLocatedSourcesPaths(
        string $installationPath,
        SourceLocator $expectedSourceLocator
    ) : void {
        self::assertEquals($expectedSourceLocator, $this->locateSources->__invoke($installationPath));
    }

    /** @param string[] $directories */
    private function assertDirectoriesInAggregateSourceLocatorMatch(AggregateSourceLocator $sourceLocator, array $directories) : void
    {
        $internalLocators = $this->getObjectAttribute($sourceLocator, 'sourceLocators');

        self::assertInternalType('array', $internalLocators);
        self::assertEquals(new DirectoriesSourceLocator($directories, $this->astLocator()), $internalLocators[0]);
    }

    /** @return string[][]|SourceLocator[][] */
    public function locatedSourcesExamples() : array
    {
        return [
            'empty composer definition' => [
                __DIR__ . '/../../asset/located-sources/empty',
                new AggregateSourceLocator([
                    new DirectoriesSourceLocator([], $this->astLocator()),
                ])
            ],
            'composer definition with everything' => [
                __DIR__ . '/../../asset/located-sources/composer-definition-with-everything',
                new AggregateSourceLocator([
                    new DirectoriesSourceLocator(
                        [
                            $this->realPath(__DIR__ . '/../../asset/located-sources/composer-definition-with-everything/foo0'),
                            $this->realPath(__DIR__ . '/../../asset/located-sources/composer-definition-with-everything/bar4'),
                            $this->realPath(__DIR__ . '/../../asset/located-sources/composer-definition-with-everything/baz4_0'),
                            $this->realPath(__DIR__ . '/../../asset/located-sources/composer-definition-with-everything/baz4_1'),
                            $this->realPath(__DIR__ . '/../../asset/located-sources/composer-definition-with-everything/baz4_2'),
                            $this->realPath(__DIR__ . '/../../asset/located-sources/composer-definition-with-everything/baz4_3'),
                            $this->realPath(__DIR__ . '/../../asset/located-sources/composer-definition-with-everything/classmap0'),
                            $this->realPath(__DIR__ . '/../../asset/located-sources/composer-definition-with-everything/classmap1'),
                        ],
                        $this->astLocator()
                    ),
                    new SingleFileSourceLocator(
                        $this->realPath(__DIR__ . '/../../asset/located-sources/composer-definition-with-everything/classmap2/file.php'),
                        $this->astLocator()
                    ),
                    new SingleFileSourceLocator(
                        $this->realPath(__DIR__ . '/../../asset/located-sources/composer-definition-with-everything/files/foo.php'),
                        $this->astLocator()
                    ),
                    new SingleFileSourceLocator(
                        $this->realPath(__DIR__ . '/../../asset/located-sources/composer-definition-with-everything/files/bar.php'),
                        $this->astLocator()
                    ),
                ])
            ],
        ];
    }

    private function astLocator() : Locator
    {
        return self::$astLocator ?? self::$astLocator = (new BetterReflection())->astLocator();
    }

    private function realPath(string $path) : string
    {
        $realPath = realpath($path);

        self::assertInternalType('string', $realPath);

        return $realPath;
    }
}
