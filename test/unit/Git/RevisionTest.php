<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Git;

use PHPUnit\Framework\TestCase;
use Psl\Exception\InvariantViolationException;
use Psl\Hash;
use Psl\SecureRandom;
use Psl\Str;
use Roave\BackwardCompatibility\Git\Revision;

/** @covers \Roave\BackwardCompatibility\Git\Revision */
final class RevisionTest extends TestCase
{
    public function testFromSha1WithValidSha1(): void
    {
        $sha1 = Hash\Context::forAlgorithm(Hash\Algorithm::SHA1)
            ->update(SecureRandom\string(8))
            ->finalize();

        self::assertSame($sha1, (string) Revision::fromSha1($sha1));
    }

    public function testFromSha1WithNewlinesStillProvidesValidSha1(): void
    {
        $sha1 = Hash\Context::forAlgorithm(Hash\Algorithm::SHA1)
            ->update(SecureRandom\string(8))
            ->finalize();

        self::assertSame($sha1, (string) Revision::fromSha1($sha1 . "\n"));
    }

    /** @return string[][] */
    public static function invalidRevisionProvider(): array
    {
        return [
            [''],
            ['a'],
            [Str\repeat('a', 39)],
            [Str\repeat('a', 41)],
            [' ' . Str\repeat('a', 42)],
            [Str\repeat('a', 42) . ' '],
        ];
    }

    /** @dataProvider invalidRevisionProvider */
    public function testInvalidSha1Rejected(string $invalidRevision): void
    {
        $this->expectException(InvariantViolationException::class);
        Revision::fromSha1($invalidRevision);
    }
}
