<?php
declare(strict_types=1);

namespace RoaveTest\ApiCompare\Git;

use InvalidArgumentException;
use Roave\ApiCompare\Git\Revision;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Roave\ApiCompare\Git\Revision
 */
final class RevisionTest extends TestCase
{
    public function testFromSha1WithValidSha1() : void
    {
        $sha1 = sha1(uniqid('sha1', true));

        self::assertSame($sha1, (string)Revision::fromSha1($sha1));
    }

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
     * @param string $invalidRevision
     * @dataProvider invalidRevisionProvider
     */
    public function testInvalidSha1Rejected(string $invalidRevision) : void
    {
        $this->expectException(InvalidArgumentException::class);
        Revision::fromSha1($invalidRevision);
    }
}
