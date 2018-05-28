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
        "psr-4": {
            "TestArtifact\\": "src/"
        }
    },
    "repositories": [
        {
            "packagist.org": false
        }
    ]
}

JSON;

    private const CLASS_V1 = <<<'PHP'
<?php

namespace TestArtifact;

final class TheClass
{
    public function method(A $a)
    {
    }
}

PHP;

    private const CLASS_V2 = <<<'PHP'
<?php

namespace TestArtifact;

final class TheClass
{
    public function method(B $a)
    {
    }
}

PHP;

    private const CLASS_V3 = <<<'PHP'
<?php

namespace TestArtifact;

final class TheClass
{
    public function method(C $a)
    {
    }
}

PHP;

    private const CLASS_V4 = <<<'PHP'
<?php

namespace TestArtifact;

final class TheClass
{
    public function method(A $a)
    {
    }
}

PHP;

    /** @var string path to the sources that should be checked */
    private $sourcesRepository;

    /** @var string[] sha1 of the source versions */
    private $versions = [];

    protected function setUp() : void
    {
        parent::setUp();

        $this->sourcesRepository = tempnam(sys_get_temp_dir(), 'roave-backward-compatibility-e2e-test');

        self::assertInternalType('string', $this->sourcesRepository);
        self::assertNotEmpty($this->sourcesRepository);
        self::assertFileExists($this->sourcesRepository);

        unlink($this->sourcesRepository);
        mkdir($this->sourcesRepository);
        mkdir($this->sourcesRepository . '/src');

        self::assertDirectoryExists($this->sourcesRepository);
        self::assertDirectoryExists($this->sourcesRepository . '/src');

        (new Process('git init', $this->sourcesRepository))->mustRun();

        file_put_contents($this->sourcesRepository . '/composer.json', self::COMPOSER_MANIFEST);

        (new Process('git add -A', $this->sourcesRepository))->mustRun();
        (new Process('git commit -am "Initial commit with composer manifest"', $this->sourcesRepository))->mustRun();

        foreach ([self::CLASS_V1, self::CLASS_V2, self::CLASS_V3, self::CLASS_V4] as $key => $classCode) {
            file_put_contents($this->sourcesRepository . '/src/TheClass.php', $classCode);

            (new Process('git add -A', $this->sourcesRepository))->mustRun();
            (new Process(sprintf('git commit -am "Class sources v%d"', $key + 1), $this->sourcesRepository))->mustRun();
            $this->versions[$key] = trim((new Process('git rev-parse HEAD', $this->sourcesRepository))->mustRun()
                                                                                                      ->getOutput());
        }
    }

    protected function tearDown() : void
    {
        self::assertInternalType('string', $this->sourcesRepository);
        self::assertNotEmpty($this->sourcesRepository);
        self::assertDirectoryExists($this->sourcesRepository);

        // Need to be extremely careful with this stuff - skipping it for now
//        (new Process(['rm', '-r', $this->sourcesRepository]))->mustRun();

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
            $check->getErrorOutput() // @TODO this looks like a symfony bug - we shouldn't check STDERR, but STDOUT
        );
    }

    public function testWillNotRunWithoutTagsNorSpecifiedVersions() : void
    {
        $check = new Process(
            __DIR__ . '/../../../bin/roave-backward-compatibility-check',
            $this->sourcesRepository
        );

        self::assertSame(212, $check->run());
        self::assertContains(
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
            ],
            $this->sourcesRepository
        );

        self::assertSame(0, $check->run());
        self::assertContains(
            'No backwards-incompatible changes detected',
            $check->getErrorOutput()
        );
    }

    public function testWillPickTaggedVersionOnNoGivenFrom() : void
    {
        (new Process(
            [
                'git',
                'checkout',
                $this->versions[1],
            ],
            $this->sourcesRepository
        ))->mustRun();
        (new Process(
            [
                'git',
                'tag',
                '1.2.3',
                '-m',
                'First tag',
            ],
            $this->sourcesRepository
        ))->mustRun();

        $check = new Process(
            [
                __DIR__ . '/../../../bin/roave-backward-compatibility-check',
                '--to=' . $this->versions[2],
            ],
            $this->sourcesRepository
        );

        self::assertSame(3, $check->run());

        $errorOutput = $check->getErrorOutput();

        self::assertContains('Detected last minor version: 1.2.3', $errorOutput);
        self::assertStringEndsWith(
            <<<'EXPECTED'
[BC] CHANGED: The parameter $a of TestArtifact\TheClass#method() changed from TestArtifact\B to a non-contravariant TestArtifact\C
1 backwards-incompatible changes detected

EXPECTED
            ,
            $errorOutput // @TODO this looks like a symfony bug - we shouldn't check STDERR, but STDOUT
        );
    }

    public function testWillPickLatestTaggedVersionOnNoGivenFrom() : void
    {
        (new Process(
            [
                'git',
                'checkout',
                $this->versions[1],
            ],
            $this->sourcesRepository
        ))->mustRun();
        (new Process(
            [
                'git',
                'tag',
                '2.2.3',
                '-m',
                'First tag',
            ],
            $this->sourcesRepository
        ))->mustRun();
        (new Process(
            [
                'git',
                'checkout',
                $this->versions[3],
            ],
            $this->sourcesRepository
        ))->mustRun();
        (new Process(
            [
                'git',
                'tag',
                '1.2.3',
                '-m',
                'First tag',
            ],
            $this->sourcesRepository
        ))->mustRun();

        $check = new Process(
            [
                __DIR__ . '/../../../bin/roave-backward-compatibility-check',
                '--to=' . $this->versions[2],
            ],
            $this->sourcesRepository
        );

        self::assertSame(3, $check->run());

        $errorOutput = $check->getErrorOutput();

        self::assertContains('Detected last minor version: 2.2.3', $errorOutput);
        self::assertStringEndsWith(
            <<<'EXPECTED'
[BC] CHANGED: The parameter $a of TestArtifact\TheClass#method() changed from TestArtifact\B to a non-contravariant TestArtifact\C
1 backwards-incompatible changes detected

EXPECTED
            ,
            $errorOutput // @TODO this looks like a symfony bug - we shouldn't check STDERR, but STDOUT
        );
    }
}
