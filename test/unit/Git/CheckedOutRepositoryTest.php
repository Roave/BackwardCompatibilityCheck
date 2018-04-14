<?php
declare(strict_types=1);

namespace RoaveTest\ApiCompare\Git;

use Roave\ApiCompare\Git\CheckedOutRepository;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Roave\ApiCompare\Git\CheckedOutRepository
 */
final class CheckedOutRepositoryTest extends TestCase
{
    public function testFromPath() : void
    {
        $path = sys_get_temp_dir() . '/' . uniqid('testPath', true);
        mkdir($path, 0777, true);
        mkdir($path . '/.git');

        $checkedOutRepository = CheckedOutRepository::fromPath($path);
        self::assertSame($path, (string)$checkedOutRepository);

        rmdir($path . '/.git');
        rmdir($path);
    }
}
