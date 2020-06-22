<?php

declare(strict_types=1);

namespace RoaveE2ETest\BackwardCompatibility\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

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

        $this->sourcesRepository = tempnam(sys_get_temp_dir(), 'roave-backward-compatibility-e2e-test');

        self::assertIsString($this->sourcesRepository);
        self::assertNotEmpty($this->sourcesRepository);
        self::assertFileExists($this->sourcesRepository);

        unlink($this->sourcesRepository);
        mkdir($this->sourcesRepository);
        mkdir($this->sourcesRepository . '/src');

        self::assertDirectoryExists($this->sourcesRepository);
        self::assertDirectoryExists($this->sourcesRepository . '/src');

        (new Process(['git', 'init'], $this->sourcesRepository))->mustRun();

        file_put_contents($this->sourcesRepository . '/composer.json', self::COMPOSER_MANIFEST);

        (new Process(['git', 'add', '-A'], $this->sourcesRepository))->mustRun();
        (new Process(['git', 'commit', '-am', 'Initial commit with composer manifest'], $this->sourcesRepository))->mustRun();

        foreach (self::CLASS_VERSIONS as $key => $classCode) {
            file_put_contents($this->sourcesRepository . '/src/TheClass.php', $classCode);

            (new Process(['git', 'add', '-A'], $this->sourcesRepository))->mustRun();
            (new Process(['git', 'commit', '-am', sprintf('Class sources v%d', $key + 1)], $this->sourcesRepository))->mustRun();
            $this->versions[$key] = trim((new Process(['git', 'rev-parse', 'HEAD'], $this->sourcesRepository))->mustRun()
                                                                                                      ->getOutput());
        }
    }

    protected function tearDown() : void
    {
        self::assertNotEmpty($this->sourcesRepository);
        self::assertDirectoryExists($this->sourcesRepository);

        // Need to be extremely careful with this stuff - skipping it for now
        (new Process(['rm', '-r', $this->sourcesRepository]))->mustRun();

        parent::tearDown();
    }

    public function testWillAllowSpecifyingGitRevision() : void
    {
        $check = new Process(
            [
                __DIR__ . '/../../../bin/roave-backward-compatibility-check',
                '--from=' . $this->versions[0],
                '--to=' . $this->versions[1],
            ],
            $this->sourcesRepository
        );

        self::assertSame(3, $check->run());
        self::assertStringEndsWith(
            <<<'EXPECTED'
[BC] CHANGED: The parameter $a of TestArtifact\TheClass#method() changed from TestArtifact\A to a non-contravariant TestArtifact\B
1 backwards-incompatible changes detected

EXPECTED
            ,
            $check->getErrorOutput() // @TODO https://github.com/Roave/BackwardCompatibilityCheck/issues/79 this looks like a symfony bug - we shouldn't check STDERR, but STDOUT
        );
    }

    public function testWillNotRunWithoutTagsNorSpecifiedVersions() : void
    {
        $check = new Process(
            [__DIR__ . '/../../../bin/roave-backward-compatibility-check'],
            $this->sourcesRepository
        );

        self::assertSame(212, $check->run());
        self::assertStringContainsString(
            'Could not detect any released versions for the given repository',
            $check->getErrorOutput()
        );
    }

    public function testWillRunSuccessfullyOnNoBcBreaks() : void
    {
        $check = new Process(
            [
                __DIR__ . '/../../../bin/roave-backward-compatibility-check',
                '--from=' . $this->versions[0],
                '--to=' . $this->versions[3],
                '-vvv',
            ],
            $this->sourcesRepository
        );

        self::assertSame(0, $check->run());
        self::assertStringContainsString(
            'No backwards-incompatible changes detected',
            $check->getErrorOutput()
        );
    }

    public function testWillPickTaggedVersionOnNoGivenFrom() : void
    {
        $this->tagOnVersion('1.2.3', 1);

        $check = new Process(
            [
                __DIR__ . '/../../../bin/roave-backward-compatibility-check',
                '--to=' . $this->versions[2],
            ],
            $this->sourcesRepository
        );

        self::assertSame(3, $check->run());

        $errorOutput = $check->getErrorOutput();

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

    public function testWillPickLatestTaggedVersionOnNoGivenFrom() : void
    {
        $this->tagOnVersion('2.2.3', 1);
        $this->tagOnVersion('1.2.3', 3);

        $check = new Process(
            [
                __DIR__ . '/../../../bin/roave-backward-compatibility-check',
                '--to=' . $this->versions[2],
            ],
            $this->sourcesRepository
        );

        self::assertSame(3, $check->run());

        $errorOutput = $check->getErrorOutput();

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

    private function tagOnVersion(string $tagName, int $version) : void
    {
        (new Process(
            [
                'git',
                'checkout',
                $this->versions[$version],
            ],
            $this->sourcesRepository
        ))->mustRun();

        (new Process(
            [
                'git',
                'tag',
                $tagName,
                '-m',
                'A tag for version ' . $version,
            ],
            $this->sourcesRepository
        ))->mustRun();
    }
}
