<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Git;

use PHPUnit\Framework\TestCase;
use Psl\Dict;
use Psl\Env;
use Psl\Filesystem;
use Psl\SecureRandom;
use Psl\Shell;
use Psl\Type;
use Roave\BackwardCompatibility\Git\CheckedOutRepository;
use Roave\BackwardCompatibility\Git\GetVersionCollectionFromGitRepository;
use Version\Version;

/** @covers \Roave\BackwardCompatibility\Git\GetVersionCollectionFromGitRepository */
final class GetVersionCollectionFromGitRepositoryTest extends TestCase
{
    private CheckedOutRepository $repoPath;

    public function setUp(): void
    {
        $tmpGitRepo = Env\temp_dir() . '/api-compare-' . SecureRandom\string(8);
        Filesystem\create_directory($tmpGitRepo);
        Shell\execute('git', ['init'], $tmpGitRepo);
        Shell\execute('git', ['config', 'user.email', 'me@example.com'], $tmpGitRepo);
        Shell\execute('git', ['config', 'user.name', 'Me Again'], $tmpGitRepo);
        Filesystem\write_file($tmpGitRepo . '/test', SecureRandom\string(8));
        Shell\execute('git', ['add', '.'], $tmpGitRepo);
        Shell\execute('git', ['commit', '-m', '"whatever"'], $tmpGitRepo);

        $this->repoPath = CheckedOutRepository::fromPath($tmpGitRepo);
    }

    public function tearDown(): void
    {
        Shell\execute('rm', ['-Rf', (string) $this->repoPath]);
    }

    private function makeTag(string $tagName): void
    {
        Shell\execute('git', ['tag', $tagName], (string) $this->repoPath);
    }

    /** @return string[] */
    private function getTags(): array
    {
        return Dict\map(
            Type\vec(Type\object(Version::class))
                ->coerce(
                    (new GetVersionCollectionFromGitRepository())
                        ->fromRepository($this->repoPath),
                ),
            static function (Version $version): string {
                return $version->toString();
            },
        );
    }

    public function testFromRepository(): void
    {
        $this->makeTag('1.0.0');

        self::assertSame(['1.0.0'], $this->getTags());
    }

    public function testFromRepositoryIgnoresInvalidVersions(): void
    {
        $this->makeTag('1.0.0');
        $this->makeTag('invalid-version');
        $this->makeTag('1.1.0');

        self::assertSame(['1.0.0', '1.1.0'], $this->getTags());
    }

    public function testFromRepositoryAllowsVersionPrefix(): void
    {
        $this->makeTag('v1.0.0');

        self::assertSame(['v1.0.0'], $this->getTags());
    }
}
