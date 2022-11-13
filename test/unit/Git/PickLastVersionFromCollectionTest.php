<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Git;

use PHPUnit\Framework\TestCase;
use Psl\Exception\InvariantViolationException;
use Psl\Type;
use Roave\BackwardCompatibility\Git\PickLastVersionFromCollection;
use Version\Version;
use Version\VersionCollection;

use function array_map;

/** @covers \Roave\BackwardCompatibility\Git\PickLastVersionFromCollection */
final class PickLastVersionFromCollectionTest extends TestCase
{
    /**
     * @return array<int, array<int, string|array<int, string>>>
     * @psalm-return array<int, array{0: string, 1: list<string>}>
     */
    public function lastStableVersionForCollectionProvider(): array
    {
        return [
            ['2.2.0', ['1.1.0', '2.1.1', '2.2.0', '1.2.1']],
            ['2.2.1', ['1.1.0', '2.2.1', '2.2.0', '1.2.1']],
            ['2.2.1', ['1.2.0', '2.2.1', '2.2.0', '1.2.1']],
            ['2.2.1', ['1.2.0', '2.2.0', '2.2.1', '1.2.1']],
            ['2.2.1', ['1.2.0', '2.2.0', '2.2.0-alpha1', '2.2.1', '1.2.1']],
            ['2.2.1', ['1.2.0', '2.2.0-alpha1', '2.2.0', '2.2.1', '1.2.1']],
            ['2.6.2', ['1.1.1', '3.0.0-alpha1', '2.7.0-beta1', '2.6.2', '2.0.0', '2.6.1', '2.6.0']],
            ['1.2.1', ['1.1.0', '1.1.1', '1.2.0', '1.2.1']],
            ['1.2.0', ['1.1.0', '1.1.1', '1.2.0']],
            ['1.2.1', ['1.2.0', '1.2.1']],
            ['1.2.0', ['1.2.0']],
            ['2.2.0', ['1.1.0', '2.1.1', '2.2.0', '1.2.1', '0.12.0', '0.11.0', '0.11.1']],
            ['0.12.89', ['0.12.0', '0.11.19', '0.12.89', '0.12.10']],
        ];
    }

    /**
     * @param string[] $collectionOfVersions
     *
     * @dataProvider lastStableVersionForCollectionProvider
     */
    public function testForRepository(string $expectedVersion, array $collectionOfVersions): void
    {
        self::assertSame(
            $expectedVersion,
            (new PickLastVersionFromCollection())->forVersions(
                new VersionCollection(...array_map(static function (string $version): Version {
                    return Type\instance_of(Version::class)
                        ->coerce(Version::fromString($version));
                }, $collectionOfVersions)),
            )->toString(),
        );
    }

    public function testWillRejectEmptyCollection(): void
    {
        $pick = new PickLastVersionFromCollection();

        $this->expectException(InvariantViolationException::class);

        $pick->forVersions(new VersionCollection());
    }
}
