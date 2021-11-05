<?php

declare(strict_types=1);

namespace RoaveE2ETest\BackwardCompatibility\Command;

use PHPUnit\Framework\TestCase;
use Psl\Shell;
use Psl\Filesystem;
use Psl\Env;
use Psl\Str;

/**
 * @coversNothing
 */
final class AssertBackwardsCompatibleTest extends TestCase
{
    private const COMPOSER_MANIFEST = <<<'JSON'
{
    "autoload": {
        "classmap": [
            "src/"
        ]
    },
    "repositories": [
        {
            "packagist.org": false
        }
    ]
}

JSON;

    private const CLASS_VERSIONS = [
        <<<'PHP'
<?php

namespace TestArtifact;

interface A {}
interface B {}
interface C {}

final class TheClass
{
    public function method(A $a)
    {
    }
}

PHP
        ,
        <<<'PHP'
<?php

namespace TestArtifact;

interface A {}
interface B {}
interface C {}

final class TheClass
{
    public function method(B $a)
    {
    }
}

PHP
        ,
        <<<'PHP'
<?php

namespace TestArtifact;

interface A {}
interface B {}
interface C {}

final class TheClass
{
    public function method(C $a)
    {
    }
}

PHP
        ,
        // The last version resets the class to its initial state
        <<<'PHP'
<?php

namespace TestArtifact;

interface A {}
interface B {}
interface C {}

final class TheClass
{
    public function method(A $a)
    {
    }
}

PHP
    ];

    /** @var string path to the sources that should be checked */
    private string $sourcesRepository;

    /** @var string[] sha1 of the source versions */
    private array $versions = [];

    protected function setUp() : void
    {
        parent::setUp();

        $this->sourcesRepository = Filesystem\create_temporary_file(Env\temp_dir(), 'roave-backward-compatibility-e2e-test');

        self::assertNotEmpty($this->sourcesRepository);
        self::assertFileExists($this->sourcesRepository);

        Filesystem\delete_file($this->sourcesRepository);
        Filesystem\create_directory($this->sourcesRepository);
        Filesystem\create_directory($this->sourcesRepository . '/src');

        self::assertDirectoryExists($this->sourcesRepository);
        self::assertDirectoryExists($this->sourcesRepository . '/src');

        Shell\execute('git', ['init'], $this->sourcesRepository);
        Shell\execute('git', ['config', 'user.email', 'me@example.com'], $this->sourcesRepository);
        Shell\execute('git', ['config', 'user.name', 'Just Me'], $this->sourcesRepository);

        Filesystem\write_file($this->sourcesRepository . '/composer.json', self::COMPOSER_MANIFEST);

        Shell\execute('git', ['add', '-A'], $this->sourcesRepository);
        Shell\execute('git', ['commit', '-am', 'Initial commit with composer manifest'], $this->sourcesRepository);

        foreach (self::CLASS_VERSIONS as $key => $classCode) {
            Filesystem\write_file($this->sourcesRepository . '/src/TheClass.php', $classCode);

            Shell\execute('git', ['add', '-A'], $this->sourcesRepository);
            Shell\execute('git', ['commit', '-am', Str\format('Class sources v%d', $key + 1)], $this->sourcesRepository);
            $this->versions[$key] = Str\trim(Shell\execute('git', ['rev-parse', 'HEAD'], $this->sourcesRepository));
        }
    }

    protected function tearDown() : void
    {
        self::assertNotEmpty($this->sourcesRepository);
        self::assertDirectoryExists($this->sourcesRepository);

        // Need to be extremely careful with this stuff - skipping it for now
        Shell\execute('rm', ['-rf', $this->sourcesRepository]);

        parent::tearDown();
    }

    public function testWillAllowSpecifyingGitRevision() : void
    {
        try {
            Shell\execute(__DIR__ . '/../../../bin/roave-backward-compatibility-check', [
                '--from=' . $this->versions[0],
                '--to=' . $this->versions[1],
            ], $this->sourcesRepository);
        } catch (Shell\Exception\FailedExecutionException $exception) {
            self::assertStringEndsWith(
                <<<'EXPECTED'
[BC] CHANGED: The parameter $a of TestArtifact\TheClass#method() changed from TestArtifact\A to a non-contravariant TestArtifact\B
1 backwards-incompatible changes detected

EXPECTED
                ,
                $exception->getErrorOutput() // @TODO https://github.com/Roave/BackwardCompatibilityCheck/issues/79 this looks like a symfony bug - we shouldn't check STDERR, but STDOUT
            );
            self::assertSame(3, $exception->getCode());
        }
    }

    public function testWillNotRunWithoutTagsNorSpecifiedVersions() : void
    {
        try {
            Shell\execute(__DIR__ . '/../../../bin/roave-backward-compatibility-check', [], $this->sourcesRepository);
        } catch (Shell\Exception\FailedExecutionException $exception) {
            self::assertSame(1, $exception->getCode());
            self::assertStringContainsString(
                'Could not detect any released versions for the given repository',
                $exception->getErrorOutput()
            );
        }
    }

    public function testWillRunSuccessfullyOnNoBcBreaks() : void
    {
        $output = Shell\execute(__DIR__ . '/../../../bin/roave-backward-compatibility-check', [
            '--from=' . $this->versions[0],
            '--to=' . $this->versions[3],
            '-vvv',
        ], $this->sourcesRepository);

        self::assertEmpty($output);
    }

    public function testWillPickTaggedVersionOnNoGivenFrom() : void
    {
        $this->tagOnVersion('1.2.3', 1);

        try {
            Shell\execute(__DIR__ . '/../../../bin/roave-backward-compatibility-check', [
                '--to=' . $this->versions[2],
            ], $this->sourcesRepository);
        } catch (Shell\Exception\FailedExecutionException $exception) {
            self::assertSame(3, $exception->getCode());

            $errorOutput = $exception->getErrorOutput();

            self::assertStringContainsString('Detected last minor version: 1.2.3', $errorOutput);
            self::assertStringEndsWith(
                <<<'EXPECTED'
[BC] CHANGED: The parameter $a of TestArtifact\TheClass#method() changed from TestArtifact\B to a non-contravariant TestArtifact\C
1 backwards-incompatible changes detected

EXPECTED
                ,
                $errorOutput // @TODO https://github.com/Roave/BackwardCompatibilityCheck/issues/79 this looks like a symfony bug - we shouldn't check STDERR, but STDOUT
            );
        }
    }

    public function testWillPickLatestTaggedVersionOnNoGivenFrom() : void
    {
        $this->tagOnVersion('2.2.3', 1);
        $this->tagOnVersion('1.2.3', 3);

        try {
            Shell\execute(__DIR__ . '/../../../bin/roave-backward-compatibility-check', [
                '--to=' . $this->versions[2],
            ], $this->sourcesRepository);
        } catch (Shell\Exception\FailedExecutionException $exception) {
            self::assertSame(3, $exception->getCode());

            $errorOutput = $exception->getErrorOutput();

            self::assertStringContainsString('Detected last minor version: 2.2.3', $errorOutput);
            self::assertStringEndsWith(
                <<<'EXPECTED'
[BC] CHANGED: The parameter $a of TestArtifact\TheClass#method() changed from TestArtifact\B to a non-contravariant TestArtifact\C
1 backwards-incompatible changes detected

EXPECTED
                ,
                $errorOutput // @TODO https://github.com/Roave/BackwardCompatibilityCheck/issues/79 this looks like a symfony bug - we shouldn't check STDERR, but STDOUT
            );
        }
    }

    private function tagOnVersion(string $tagName, int $version) : void
    {
        Shell\execute('git', ['checkout', $this->versions[$version]], $this->sourcesRepository);
        Shell\execute('git', ['tag', $tagName, '-m', 'A tag for version ' . $version,], $this->sourcesRepository);
    }
}
