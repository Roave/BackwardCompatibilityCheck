<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Git;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Git\CheckedOutRepository;
use Roave\ApiCompare\Git\GitCheckoutRevisionToTemporaryPath;
use Roave\ApiCompare\Git\Revision;
use function realpath;

/**
 * @covers \Roave\ApiCompare\Git\GitCheckoutRevisionToTemporaryPath
 */
final class GitCheckoutRevisionToTemporaryPathTest extends TestCase
{
    public function testCheckoutAndRemove() : void
    {
        $sourceRepositoryPath = realpath(__DIR__ . '/../../../');

        $git = new GitCheckoutRevisionToTemporaryPath();

        $temporaryClone = $git->checkout(
            CheckedOutRepository::fromPath($sourceRepositoryPath),
            Revision::fromSha1('428327492a803b6e0c612b157a67a50a47275461')
        );

        self::assertInstanceOf(CheckedOutRepository::class, $temporaryClone);

        $git->remove($temporaryClone);
    }
}
