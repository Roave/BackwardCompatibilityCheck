<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Git;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Git\CheckedOutRepository;
use Roave\BackwardCompatibility\Git\GetVersionCollectionFromGitRepository;
use Symfony\Component\Process\Process;
use Version\Version;
use function array_map;
use function iterator_to_array;
use function Safe\file_put_contents;
use function Safe\mkdir;
use function sys_get_temp_dir;
use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\Git\GetVersionCollectionFromGitRepository
 */
final class GetVersionCollectionFromGitRepositoryTest extends TestCase
{
    /** @var CheckedOutRepository */
    private $repoPath;

    public function setUp() : void
    {
        $tmpGitRepo = sys_get_temp_dir() . '/api-compare-' . uniqid('tmpGitRepo', true);
        mkdir($tmpGitRepo, 0777, true);
        (new Process(['git', 'init']))->setWorkingDirectory($tmpGitRepo)->mustRun();
        file_put_contents($tmpGitRepo . '/test', uniqid('testContent', true));
        (new Process(['git', 'add', '.']))->setWorkingDirectory($tmpGitRepo)->mustRun();
        (new Process(['git', 'commit', '-m', '"whatever"']))->setWorkingDirectory($tmpGitRepo)->mustRun();
        $this->repoPath = CheckedOutRepository::fromPath($tmpGitRepo);
    }

    public function tearDown() : void
    {
        (new Process(['rm', '-Rf', (string) $this->repoPath]))->mustRun();
    }

    private function makeTag(string $tagName) : void
    {
        (new Process(['git', 'tag', $tagName]))->setWorkingDirectory((string) $this->repoPath)->mustRun();
    }

    /** @return string[] */
    private function getTags() : array
    {
        return array_map(
            static function (Version $version) : string {
                return $version->toString();
            },
            iterator_to_array((new GetVersionCollectionFromGitRepository())->fromRepository($this->repoPath))
        );
    }

    public function testFromRepository() : void
    {
        $this->makeTag('1.0.0');

        self::assertSame(['1.0.0'], $this->getTags());
    }

    public function testFromRepositoryIgnoresInvalidVersions() : void
    {
        $this->makeTag('1.0.0');
        $this->makeTag('invalid-version');
        $this->makeTag('1.1.0');

        self::assertSame(['1.0.0', '1.1.0'], $this->getTags());
    }

    public function testFromRepositoryAllowsVersionPrefix() : void
    {
        $this->makeTag('v1.0.0');

        self::assertSame(['v1.0.0'], $this->getTags());
    }
}
