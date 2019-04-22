<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Git;

use Assert\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Git\PickLastMinorVersionFromCollection;
use Version\Version;
use Version\VersionsCollection;
use function array_map;

/**
 * @covers \Roave\BackwardCompatibility\Git\PickLastMinorVersionFromCollection
 */
final class PickLastMinorVersionFromCollectionTest extends TestCase
{
    /**
     * @return string[][]|string[][][]
     */
    public function lastStableMinorVersionForCollectionProvider() : array
    {
        return [
            ['2.2.0', ['1.1.0', '2.1.1', '2.2.0', '1.2.1']],
            ['2.2.0', ['1.1.0', '2.2.1', '2.2.0', '1.2.1']],
            ['2.2.0', ['1.2.0', '2.2.1', '2.2.0', '1.2.1']],
            ['2.2.0', ['1.2.0', '2.2.0', '2.2.1', '1.2.1']],
            ['2.2.0', ['1.2.0', '2.2.0', '2.2.0-alpha1', '2.2.1', '1.2.1']],
            ['2.2.0', ['1.2.0', '2.2.0-alpha1', '2.2.0', '2.2.1', '1.2.1']],
            ['2.6.0', ['1.1.1', '3.0.0-alpha1', '2.7.0-beta1', '2.6.2', '2.0.0', '2.6.1', '2.6.0']],
            ['1.2.0', ['1.1.0', '1.1.1', '1.2.0', '1.2.1']],
            ['1.2.0', ['1.1.0', '1.1.1', '1.2.0']],
            ['1.2.0', ['1.2.0', '1.2.1']],
            ['1.2.0', ['1.2.0']],
        ];
    }

    /**
     * @param string[] $collectionOfVersions
     *
     * @dataProvider lastStableMinorVersionForCollectionProvider
     */
    public function testForRepository(string $expectedVersion, array $collectionOfVersions) : void
    {
        self::assertSame(
            $expectedVersion,
            (new PickLastMinorVersionFromCollection())->forVersions(
                new VersionsCollection(...array_map(static function (string $version) : Version {
                    return Version::fromString($version);
                }, $collectionOfVersions))
            )->getVersionString()
        );
    }

    public function testWillRejectEmptyCollection() : void
    {
        $pick = new PickLastMinorVersionFromCollection();

        $this->expectException(AssertionFailedException::class);

        $pick->forVersions(new VersionsCollection());
    }
}
