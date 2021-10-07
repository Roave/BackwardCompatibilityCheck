<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Command;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psl\Env;
use Psl\Exception\InvariantViolationException;
use Psl\Filesystem;
use Psl\Hash;
use Psl\SecureRandom;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Command\AssertBackwardsCompatible;
use Roave\BackwardCompatibility\CompareApi;
use Roave\BackwardCompatibility\Factory\ComposerInstallationReflectorFactory;
use Roave\BackwardCompatibility\Git\CheckedOutRepository;
use Roave\BackwardCompatibility\Git\GetVersionCollection;
use Roave\BackwardCompatibility\Git\ParseRevision;
use Roave\BackwardCompatibility\Git\PerformCheckoutOfRevision;
use Roave\BackwardCompatibility\Git\PickVersionFromVersionCollection;
use Roave\BackwardCompatibility\Git\Revision;
use Roave\BackwardCompatibility\LocateDependencies\LocateDependencies;
use Roave\BackwardCompatibility\LocateSources\LocateSourcesViaComposerJson;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Version\Version;
use Version\VersionCollection;

use function assert;
use function is_string;

/**
 * @covers \Roave\BackwardCompatibility\Command\AssertBackwardsCompatible
 */
final class AssertBackwardsCompatibleTest extends TestCase
{
    private CheckedOutRepository $sourceRepository;

    /** @var InputInterface&MockObject */
    private InputInterface $input;

    /** @var ConsoleOutputInterface&MockObject */
    private ConsoleOutputInterface $output;

    /** @var OutputInterface&MockObject */
    private OutputInterface $stdErr;

    /** @var PerformCheckoutOfRevision&MockObject */
    private PerformCheckoutOfRevision $performCheckout;

    /** @var ParseRevision&MockObject */
    private ParseRevision $parseRevision;

    /** @var GetVersionCollection&MockObject */
    private GetVersionCollection $getVersions;

    /** @var PickVersionFromVersionCollection&MockObject */
    private PickVersionFromVersionCollection $pickVersion;

    /** @var LocateDependencies&MockObject */
    private LocateDependencies $locateDependencies;

    /** @var CompareApi&MockObject */
    private CompareApi $compareApi;

    private AggregateSourceLocator $dependencies;

    private AssertBackwardsCompatible $compare;

    public function setUp(): void
    {
        $repositoryPath = Filesystem\canonicalize(__DIR__ . '/../../../');
        assert(is_string($repositoryPath));

        $this->sourceRepository = CheckedOutRepository::fromPath($repositoryPath);

        Env\set_current_dir($this->sourceRepository->__toString());

        $this->input              = $this->createMock(InputInterface::class);
        $this->output             = $this->createMock(ConsoleOutputInterface::class);
        $this->stdErr             = $this->createMock(OutputInterface::class);
        $this->performCheckout    = $this->createMock(PerformCheckoutOfRevision::class);
        $this->parseRevision      = $this->createMock(ParseRevision::class);
        $this->getVersions        = $this->createMock(GetVersionCollection::class);
        $this->pickVersion        = $this->createMock(PickVersionFromVersionCollection::class);
        $this->locateDependencies = $this->createMock(LocateDependencies::class);
        $this->dependencies       = new AggregateSourceLocator();
        $this->compareApi         = $this->createMock(CompareApi::class);
        $this->compare            = new AssertBackwardsCompatible(
            $this->performCheckout,
            new ComposerInstallationReflectorFactory(new LocateSourcesViaComposerJson((new BetterReflection())->astLocator())),
            $this->parseRevision,
            $this->getVersions,
            $this->pickVersion,
            $this->locateDependencies,
            $this->compareApi
        );

        $this
            ->output
            ->method('getErrorOutput')
            ->willReturn($this->stdErr);
    }

    public function testDefinition(): void
    {
        self::assertSame(
            'roave-backwards-compatibility-check:assert-backwards-compatible',
            $this->compare->getName()
        );

        $usages = $this->compare->getUsages();

        self::assertCount(1, $usages);
        self::assertStringStartsWith(
            'roave-backwards-compatibility-check:assert-backwards-compatible',
            $usages[0]
        );
        self::assertStringContainsString('Without arguments, this command will attempt to detect', $usages[0]);
        self::assertStringStartsWith('Verifies that the revision being', $this->compare->getDescription());

        self::assertSame(
            '[--from [FROM]] [--to TO] [--format [FORMAT]] [--install-development-dependencies]',
            $this->compare
                ->getDefinition()
                ->getSynopsis()
        );
    }

    public function testExecuteWhenRevisionsAreProvidedAsOptions(): void
    {
        $fromSha = Hash\Context::forAlgorithm('sha1')->update('fromRevision')->finalize();
        $toSha   = Hash\Context::forAlgorithm('sha1')->update('toRevision')->finalize();

        $this->input->method('getOption')->willReturnMap([
            ['from', $fromSha],
            ['to', $toSha],
            ['install-development-dependencies', false],
        ]);
        $this->input->method('getArgument')->willReturnMap([
            ['sources-path', 'src'],
        ]);

        $this->performCheckout->expects(self::exactly(2))
            ->method('checkout')
            ->withConsecutive(
                [$this->sourceRepository, $fromSha],
                [$this->sourceRepository, $toSha]
            )->willReturnOnConsecutiveCalls(
                $this->sourceRepository,
                $this->sourceRepository
            );

        $this->performCheckout->expects(self::exactly(2))
            ->method('remove')
            ->withConsecutive(
                [$this->sourceRepository],
                [$this->sourceRepository]
            );

        $this->parseRevision->expects(self::exactly(2))
            ->method('fromStringForRepository')
            ->withConsecutive(
                [$fromSha],
                [$toSha]
            )->willReturnOnConsecutiveCalls(
                Revision::fromSha1($fromSha),
                Revision::fromSha1($toSha)
            );

        $this
            ->locateDependencies

            ->method('__invoke')
            ->with((string) $this->sourceRepository, false)
            ->willReturn($this->dependencies);

        $this->compareApi->expects(self::once())->method('__invoke')->willReturn(Changes::empty());

        self::assertSame(0, $this->compare->execute($this->input, $this->output));
    }

    public function testExecuteWhenDevelopmentDependenciesAreRequested(): void
    {
        $fromSha = Hash\Context::forAlgorithm('sha1')->update('fromRevision')->finalize();
        $toSha   = Hash\Context::forAlgorithm('sha1')->update('toRevision')->finalize();

        $this->input->expects(self::any())->method('getOption')->willReturnMap([
            ['from', $fromSha],
            ['to', $toSha],
            ['install-development-dependencies', true],
        ]);
        $this->input->expects(self::any())->method('getArgument')->willReturnMap([
            ['sources-path', 'src'],
        ]);

        $this->performCheckout->expects(self::at(0))
            ->method('checkout')
            ->with($this->sourceRepository, $fromSha)
            ->willReturn($this->sourceRepository);
        $this->performCheckout->expects(self::at(1))
            ->method('checkout')
            ->with($this->sourceRepository, $toSha)
            ->willReturn($this->sourceRepository);
        $this->performCheckout->expects(self::at(2))
            ->method('remove')
            ->with($this->sourceRepository);
        $this->performCheckout->expects(self::at(3))
            ->method('remove')
            ->with($this->sourceRepository);

        $this->parseRevision->expects(self::at(0))
            ->method('fromStringForRepository')
            ->with($fromSha)
            ->willReturn(Revision::fromSha1($fromSha));
        $this->parseRevision->expects(self::at(1))
            ->method('fromStringForRepository')
            ->with($toSha)
            ->willReturn(Revision::fromSha1($toSha));

        $this
            ->locateDependencies
            ->expects(self::any())
            ->method('__invoke')
            ->with((string) $this->sourceRepository, true)
            ->willReturn($this->dependencies);

        $this->compareApi->expects(self::once())->method('__invoke')->willReturn(Changes::empty());

        self::assertSame(0, $this->compare->execute($this->input, $this->output));
    }

    public function testExecuteReturnsNonZeroExitCodeWhenChangesAreDetected(): void
    {
        $fromSha = Hash\Context::forAlgorithm('sha1')->update('fromRevision')->finalize();
        $toSha   = Hash\Context::forAlgorithm('sha1')->update('toRevision')->finalize();

        $this->input->method('getOption')->willReturnMap([
            ['from', $fromSha],
            ['to', $toSha],
            ['install-development-dependencies', false],
        ]);
        $this->input->method('getArgument')->willReturnMap([
            ['sources-path', 'src'],
        ]);

        $this->performCheckout->expects(self::exactly(2))
            ->method('checkout')
            ->withConsecutive(
                [$this->sourceRepository, $fromSha],
                [$this->sourceRepository, $toSha]
            )->willReturnOnConsecutiveCalls(
                $this->sourceRepository,
                $this->sourceRepository
            );

        $this->performCheckout->expects(self::exactly(2))
            ->method('remove')
            ->withConsecutive(
                [$this->sourceRepository],
                [$this->sourceRepository]
            );

        $this->parseRevision->expects(self::exactly(2))
            ->method('fromStringForRepository')
            ->withConsecutive(
                [$fromSha],
                [$toSha]
            )->willReturnOnConsecutiveCalls(
                Revision::fromSha1($fromSha),
                Revision::fromSha1($toSha)
            );

        $this
            ->locateDependencies
            ->method('__invoke')
            ->with((string) $this->sourceRepository, false)
            ->willReturn($this->dependencies);

        $this->compareApi->expects(self::once())->method('__invoke')->willReturn(Changes::fromList(
            Change::added('added' . SecureRandom\string(8), true)
        ));

        $this
            ->stdErr
            ->expects(self::exactly(3))
            ->method('writeln')
            ->with(self::logicalOr(
                self::matches('Comparing from %a to %a...'),
                self::matches('[BC] ADDED: added%a'),
                self::matches('<error>1 backwards-incompatible changes detected</error>')
            ));

        self::assertSame(3, $this->compare->execute($this->input, $this->output));
    }

    public function testProvidingMarkdownOptionWritesMarkdownOutput(): void
    {
        $fromSha = Hash\Context::forAlgorithm('sha1')->update('fromRevision')->finalize();
        $toSha   = Hash\Context::forAlgorithm('sha1')->update('toRevision')->finalize();

        $this->input->method('getOption')->willReturnMap([
            ['from', $fromSha],
            ['to', $toSha],
            ['format', ['markdown']],
            ['install-development-dependencies', false],
        ]);
        $this->input->method('getArgument')->willReturnMap([
            ['sources-path', 'src'],
        ]);

        $this->performCheckout->expects(self::exactly(2))
            ->method('checkout')
            ->withConsecutive(
                [$this->sourceRepository, $fromSha],
                [$this->sourceRepository, $toSha],
            )->willReturnOnConsecutiveCalls(
                $this->sourceRepository,
                $this->sourceRepository
            );

        $this->performCheckout->expects(self::exactly(2))
            ->method('remove')
            ->withConsecutive(
                [$this->sourceRepository],
                [$this->sourceRepository]
            );

        $this->parseRevision->expects(self::exactly(2))
            ->method('fromStringForRepository')
            ->withConsecutive(
                [$fromSha],
                [$toSha]
            )->willReturnOnConsecutiveCalls(
                Revision::fromSha1($fromSha),
                Revision::fromSha1($toSha)
            );

        $this
            ->locateDependencies
            ->method('__invoke')
            ->with((string) $this->sourceRepository, false)
            ->willReturn($this->dependencies);

        $changeToExpect = SecureRandom\string(8);
        $this->compareApi->expects(self::once())->method('__invoke')->willReturn(Changes::fromList(
            Change::removed($changeToExpect, true)
        ));

        $this->output
            ->expects(self::once())
            ->method('writeln')
            ->willReturnCallback(static function (string $output) use ($changeToExpect): void {
                self::assertStringContainsString(' [BC] ' . $changeToExpect, $output);
            });

        $this->compare->execute($this->input, $this->output);
    }

    public function testExecuteWithDefaultRevisionsNotProvidedAndNoDetectedTags(): void
    {
        $this->input->method('getOption')->willReturnMap([
            ['from', null],
            ['to', 'HEAD'],
        ]);
        $this->input->method('getArgument')->willReturnMap([
            ['sources-path', 'src'],
        ]);

        $this
            ->performCheckout
            ->expects(self::never())
            ->method('checkout');
        $this
            ->parseRevision
            ->expects(self::never())
            ->method('fromStringForRepository');

        $this
            ->getVersions
            ->expects(self::once())
            ->method('fromRepository')
            ->willReturn(new VersionCollection());
        $this
            ->pickVersion
            ->expects(self::never())
            ->method('forVersions');
        $this
            ->compareApi
            ->expects(self::never())
            ->method('__invoke');

        $this->expectException(InvariantViolationException::class);

        $this->compare->execute($this->input, $this->output);
    }

    /**
     * @dataProvider validVersionCollections
     */
    public function testExecuteWithDefaultRevisionsNotProvided(VersionCollection $versions): void
    {
        $fromSha       = Hash\Context::forAlgorithm('sha1')->update('fromRevision')->finalize();
        $toSha         = Hash\Context::forAlgorithm('sha1')->update('toRevision')->finalize();
        $pickedVersion = Version::fromString('1.0.0');

        $this->input->method('getOption')->willReturnMap([
            ['from', null],
            ['to', 'HEAD'],
            ['install-development-dependencies', false],
        ]);
        $this->input->method('getArgument')->willReturnMap([
            ['sources-path', 'src'],
        ]);

        $this->performCheckout->expects(self::exactly(2))
            ->method('checkout')
            ->withConsecutive(
                [$this->sourceRepository, $fromSha],
                [$this->sourceRepository, $toSha]
            )->willReturnOnConsecutiveCalls(
                $this->sourceRepository,
                $this->sourceRepository
            );

        $this->performCheckout->expects(self::exactly(2))
            ->method('remove')
            ->withConsecutive(
                [$this->sourceRepository],
                [$this->sourceRepository]
            );

        $this->parseRevision->expects(self::exactly(2))
            ->method('fromStringForRepository')
            ->withConsecutive(
                [(string) $pickedVersion],
                ['HEAD']
            )->willReturnOnConsecutiveCalls(
                Revision::fromSha1($fromSha),
                Revision::fromSha1($toSha)
            );

        $this->getVersions->expects(self::once())
            ->method('fromRepository')
            ->with(self::callback(function (CheckedOutRepository $checkedOutRepository): bool {
                self::assertEquals($this->sourceRepository, $checkedOutRepository);

                return true;
            }))
            ->willReturn($versions);
        $this->pickVersion->expects(self::once())
            ->method('forVersions')
            ->with($versions)
            ->willReturn($pickedVersion);

        $this
            ->stdErr
            ->expects(self::exactly(3))
            ->method('writeln')
            ->with(self::logicalOr(
                'Detected last minor version: 1.0.0',
                self::matches('Comparing from %a to %a...'),
                self::matches('<info>No backwards-incompatible changes detected</info>')
            ));

        $this->compareApi->expects(self::once())->method('__invoke')->willReturn(Changes::empty());

        self::assertSame(0, $this->compare->execute($this->input, $this->output));
    }

    /** @return VersionCollection[][] */
    public function validVersionCollections(): array
    {
        return [
            [
                new VersionCollection(
                    Version::fromString('1.0.0'),
                    Version::fromString('1.0.1'),
                    Version::fromString('1.0.2')
                ),
            ],
            [
                new VersionCollection(
                    Version::fromString('1.0.0'),
                    Version::fromString('1.0.1')
                ),
            ],
            [new VersionCollection(Version::fromString('1.0.0'))],
        ];
    }
}
