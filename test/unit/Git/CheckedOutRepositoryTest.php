<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Git;

use PHPUnit\Framework\TestCase;
use Psl\Env;
use Psl\Exception\InvariantViolationException;
use Psl\Filesystem;
use Psl\SecureRandom;
use Roave\BackwardCompatibility\Git\CheckedOutRepository;

/** @covers \Roave\BackwardCompatibility\Git\CheckedOutRepository */
final class CheckedOutRepositoryTest extends TestCase
{
    public function testFromPath(): void
    {
        $path = Env\temp_dir() . '/' . SecureRandom\string(8);
        Filesystem\create_directory($path);
        Filesystem\create_directory($path . '/.git');

        $checkedOutRepository = CheckedOutRepository::fromPath($path);
        self::assertSame($path, (string) $checkedOutRepository);

        Filesystem\delete_directory($path . '/.git', true);
        Filesystem\delete_directory($path, true);
    }

    public function testFromPathRejectsNonGitDirectory(): void
    {
        $this->expectException(InvariantViolationException::class);

        CheckedOutRepository::fromPath(__DIR__);
    }

    public function testFromPathRejectsNonExistingDirectory(): void
    {
        $this->expectException(InvariantViolationException::class);

        CheckedOutRepository::fromPath(__DIR__ . '/non-existing');
    }
}
