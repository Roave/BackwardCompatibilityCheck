<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Git;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Git\CheckedOutRepository;

use function Safe\mkdir;
use function Safe\rmdir;
use function sys_get_temp_dir;
use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\Git\CheckedOutRepository
 */
final class CheckedOutRepositoryTest extends TestCase
{
    public function testFromPath(): void
    {
        $path = sys_get_temp_dir() . '/' . uniqid('testPath', true);
        mkdir($path, 0777, true);
        mkdir($path . '/.git');

        $checkedOutRepository = CheckedOutRepository::fromPath($path);
        self::assertSame($path, (string) $checkedOutRepository);

        rmdir($path . '/.git');
        rmdir($path);
    }

    public function testFromPathRejectsNonGitDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CheckedOutRepository::fromPath(__DIR__);
    }

    public function testFromPathRejectsNonExistingDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CheckedOutRepository::fromPath(__DIR__ . '/non-existing');
    }
}
