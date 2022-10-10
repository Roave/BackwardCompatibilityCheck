<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Git;

use PHPUnit\Framework\TestCase;
use Psl\Env;
use Psl\File;
use Psl\Filesystem;
use Psl\Shell;
use Psl\Type;
use Roave\BackwardCompatibility\Git\CheckedOutRepository;
use Roave\BackwardCompatibility\Git\GitCheckoutRevisionToTemporaryPath;
use Roave\BackwardCompatibility\Git\Revision;
use RuntimeException;

/** @covers \Roave\BackwardCompatibility\Git\GitCheckoutRevisionToTemporaryPath */
final class GitCheckoutRevisionToTemporaryPathTest extends TestCase
{
    private const TEST_REVISION_TO_CHECKOUT = '428327492a803b6e0c612b157a67a50a47275461';

    public function testCheckoutAndRemove(): void
    {
        $git      = new GitCheckoutRevisionToTemporaryPath();
        $revision = Revision::fromSha1(self::TEST_REVISION_TO_CHECKOUT);

        $temporaryClone = $git->checkout($this->sourceRepository(), $revision);

        self::assertDirectoryExists((string) $temporaryClone);

        $git->remove($temporaryClone);

        self::assertDirectoryDoesNotExist((string) $temporaryClone);
    }

    public function testCanCheckOutSameRevisionTwice(): void
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

        self::assertDirectoryDoesNotExist((string) $first);
        self::assertDirectoryDoesNotExist((string) $second);
    }

    public function testCheckedOutRevisionIsAtExpectedRevisionState(): void
    {
        $repoPath = Filesystem\create_temporary_file(Env\temp_dir(), 'test-git-repo-');

        Filesystem\delete_file($repoPath);
        Filesystem\create_directory($repoPath);

        Shell\execute('git', ['init'], $repoPath);
        Shell\execute('git', ['config', 'user.email', 'me@example.com'], $repoPath);
        Shell\execute('git', ['config', 'user.name', 'Mr Magoo'], $repoPath);
        Shell\execute('git', ['commit', '-m', 'initial commit', '--allow-empty'], $repoPath);

        $firstCommit = Revision::fromSha1(
            Shell\execute('git', ['rev-parse', 'HEAD'], $repoPath),
        );

        File\write($repoPath . '/a-file.txt', 'file contents');

        Shell\execute('git', ['add', 'a-file.txt'], $repoPath);
        Shell\execute('git', ['commit', '-m', 'second commit', '--allow-empty'], $repoPath);

        $secondCommit = Revision::fromSha1(
            Shell\execute('git', ['rev-parse', 'HEAD'], $repoPath),
        );

        $git = new GitCheckoutRevisionToTemporaryPath();

        $sourceRepository = CheckedOutRepository::fromPath($repoPath);
        $first            = $git->checkout($sourceRepository, $firstCommit);
        $second           = $git->checkout($sourceRepository, $secondCommit);

        self::assertFileDoesNotExist($first->__toString() . '/a-file.txt');
        self::assertFileExists($second->__toString() . '/a-file.txt');

        $git->remove($first);
        $git->remove($second);

        Shell\execute('rm', ['-rf', $repoPath]);
    }

    public function testExceptionIsThrownWhenTwoPathsCollide(): void
    {
        $git              = new GitCheckoutRevisionToTemporaryPath(static function (): string {
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
                $runtimeException->getMessage(),
            );
        } finally {
            $git->remove($first);
        }

        self::assertFalse($successfullyCheckedOutSecondClone);
    }

    private function sourceRepository(): CheckedOutRepository
    {
        return CheckedOutRepository::fromPath(
            Type\non_empty_string()
                ->assert(Filesystem\canonicalize(__DIR__ . '/../../..')),
        );
    }
}
