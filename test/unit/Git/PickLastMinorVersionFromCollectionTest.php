<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Git;

use PHPUnit\Framework\TestCase;
use Psl\Exception\InvariantViolationException;
use Psl\Type;
use Roave\BackwardCompatibility\Git\PickLastMinorVersionFromCollection;
use Version\Version;
use Version\VersionCollection;

use function array_map;

/** @covers \Roave\BackwardCompatibility\Git\PickLastMinorVersionFromCollection */
final class PickLastMinorVersionFromCollectionTest extends TestCase
{
    /**
     * @return array<int, array<int, string|array<int, string>>>
     * @psalm-return array<int, array{0: string, 1: list<string>}>
     */
    public function lastStableMinorVersionForCollectionProvider(): array
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
    public function testForRepository(string $expectedVersion, array $collectionOfVersions): void
    {
        self::assertSame(
            $expectedVersion,
            (new PickLastMinorVersionFromCollection())->forVersions(
                new VersionCollection(...array_map(static function (string $version): Version {
                    return Type\instance_of(Version::class)
                        ->coerce(Version::fromString($version));
                }, $collectionOfVersions)),
            )->toString(),
        );
    }

    public function testWillRejectEmptyCollection(): void
    {
        $pick = new PickLastMinorVersionFromCollection();

        $this->expectException(InvariantViolationException::class);

        $pick->forVersions(new VersionCollection());
    }
}
