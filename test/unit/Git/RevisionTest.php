<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Git;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Git\Revision;
use function sha1;
use function str_repeat;
use function uniqid;

/**
 * @covers \Roave\ApiCompare\Git\Revision
 */
final class RevisionTest extends TestCase
{
    public function testFromSha1WithValidSha1() : void
    {
        $sha1 = sha1(uniqid('sha1', true));

        self::assertSame($sha1, (string) Revision::fromSha1($sha1));
    }

    /**
     * @return string[][]
     */
    public function invalidRevisionProvider() : array
    {
        return [
            [''],
            ['a'],
            [str_repeat('a', 39)],
            [str_repeat('a', 41)],
        ];
    }

    /**
     * @dataProvider invalidRevisionProvider
     */
    public function testInvalidSha1Rejected(string $invalidRevision) : void
    {
        $this->expectException(InvalidArgumentException::class);
        Revision::fromSha1($invalidRevision);
    }
}
