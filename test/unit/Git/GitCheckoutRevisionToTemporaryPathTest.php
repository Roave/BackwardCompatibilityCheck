<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Git;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Git\CheckedOutRepository;
use Roave\BackwardCompatibility\Git\GitCheckoutRevisionToTemporaryPath;
use Roave\BackwardCompatibility\Git\Revision;
use RuntimeException;
use function Safe\realpath;

/**
 * @covers \Roave\BackwardCompatibility\Git\GitCheckoutRevisionToTemporaryPath
 */
final class GitCheckoutRevisionToTemporaryPathTest extends TestCase
{
    private const TEST_REVISION_TO_CHECKOUT = '428327492a803b6e0c612b157a67a50a47275461';

    public function testCheckoutAndRemove() : void
    {
        $git      = new GitCheckoutRevisionToTemporaryPath();
        $revision = Revision::fromSha1(self::TEST_REVISION_TO_CHECKOUT);

        $temporaryClone = $git->checkout($this->sourceRepository(), $revision);

        self::assertDirectoryExists((string) $temporaryClone);

        $git->remove($temporaryClone);
    }

    public function testCanCheckOutSameRevisionTwice() : void
    {
        $git              = new GitCheckoutRevisionToTemporaryPath();
        $sourceRepository = $this->sourceRepository();
        $revision         = Revision::fromSha1(self::TEST_REVISION_TO_CHECKOUT);

        $first  = $git->checkout($sourceRepository, $revision);
        $second = $git->checkout($sourceRepository, $revision);

        self::assertDirectoryExists((string) $first);
        self::assertDirectoryExists((string) $second);

        $git->remove($first);
        $git->remove($second);
    }

    public function testExceptionIsThrownWhenTwoPathsCollide() : void
    {
        $git              = new GitCheckoutRevisionToTemporaryPath(static function () : string {
            return 'foo';
        });
        $sourceRepository = $this->sourceRepository();
        $revision         = Revision::fromSha1(self::TEST_REVISION_TO_CHECKOUT);

        $first = $git->checkout($sourceRepository, $revision);

        $successfullyCheckedOutSecondClone = false;

        try {
            $second                            = $git->checkout($sourceRepository, $revision);
            $successfullyCheckedOutSecondClone = true;
            $git->remove($second);
        } catch (RuntimeException $runtimeException) {
            self::assertStringMatchesFormat(
                'Tried to check out revision "%s" to directory "%s" which already exists',
                $runtimeException->getMessage()
            );
        } finally {
            $git->remove($first);
        }

        self::assertFalse($successfullyCheckedOutSecondClone);
    }

    private function sourceRepository() : CheckedOutRepository
    {
        $repositoryPath = realpath(__DIR__ . '/../../..');

        self::assertInternalType('string', $repositoryPath);

        return CheckedOutRepository::fromPath($repositoryPath);
    }
}
