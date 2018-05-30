<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Git;

use InvalidArgumentException;
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
            ['1.2.0', ['1.1.0', '1.1.1', '1.2.0', '1.2.1']],
            ['1.2.0', ['1.1.0', '1.1.1', '1.2.0']],
            ['1.2.0', ['1.2.0', '1.2.1']],
            ['1.2.0', ['1.2.0']],
        ];
    }

    /**
     * @param string[] $collectionOfVersions
     * @dataProvider lastStableMinorVersionForCollectionProvider
     */
    public function testForRepository(string $expectedVersion, array $collectionOfVersions) : void
    {
        self::assertSame(
            $expectedVersion,
            (new PickLastMinorVersionFromCollection())->forVersions(
                new VersionsCollection(...array_map(function (string $version) : Version {
                    return Version::fromString($version);
                }, $collectionOfVersions))
            )->getVersionString()
        );
    }

    public function testFailsIfNoVersionGiven() : void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PickLastMinorVersionFromCollection())->forVersions(new VersionsCollection());
    }
}
