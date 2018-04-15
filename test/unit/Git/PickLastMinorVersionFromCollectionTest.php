<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Git;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Git\PickLastMinorVersionFromCollection;
use Version\VersionsCollection;

/**
 * @covers \Roave\ApiCompare\Git\PickLastMinorVersionFromCollection
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
                VersionsCollection::fromArray($collectionOfVersions)
            )->getVersionString()
        );
    }

    public function testEmptyVersionCollectionResultsInException() : void
    {
        $versions   = VersionsCollection::fromArray([]);
        $determiner = new PickLastMinorVersionFromCollection();

        $this->expectException(InvalidArgumentException::class);
        $determiner->forVersions($versions);
    }
}
