<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Command;

use Assert\AssertionFailedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
use function Safe\chdir;
use function Safe\realpath;
use function sha1;
use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\Command\AssertBackwardsCompatible
 */
final class AssertBackwardsCompatibleTest extends TestCase
{
    /** @var CheckedOutRepository */
    private $sourceRepository;

    /** @var InputInterface&MockObject */
    private $input;

    /** @var ConsoleOutputInterface&MockObject */
    private $output;

    /** @var OutputInterface&MockObject */
    private $stdErr;

    /** @var PerformCheckoutOfRevision&MockObject */
    private $performCheckout;

    /** @var ParseRevision&MockObject */
    private $parseRevision;

    /** @var GetVersionCollection&MockObject */
    private $getVersions;

    /** @var PickVersionFromVersionCollection&MockObject */
    private $pickVersion;

    /** @var LocateDependencies&MockObject */
    private $locateDependencies;

    /** @var CompareApi&MockObject */
    private $compareApi;

    /** @var AggregateSourceLocator */
    private $dependencies;

    /** @var AssertBackwardsCompatible */
    private $compare;

    public function setUp() : void
    {
        $repositoryPath = realpath(__DIR__ . '/../../../');

        $this->sourceRepository = CheckedOutRepository::fromPath($repositoryPath);

        chdir($this->sourceRepository->__toString());

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
            ->expects(self::any())
            ->method('getErrorOutput')
            ->willReturn($this->stdErr);
    }

    public function testDefinition() : void
    {
        $usages = $this->compare->getUsages();

        self::assertCount(1, $usages);
        self::assertStringStartsWith(
            'roave-backwards-compatibility-check:assert-backwards-compatible',
            $usages[0]
        );
        self::assertStringContainsString('Without arguments, this command will attempt to detect', $usages[0]);
        self::assertStringStartsWith('Verifies that the revision being', $this->compare->getDescription());

        self::assertSame(
            '[--from [FROM]] [--to TO] [--format [FORMAT]]',
            $this->compare
                ->getDefinition()
                ->getSynopsis()
        );
    }

    public function testExecuteWhenRevisionsAreProvidedAsOptions() : void
    {
        $fromSha = sha1('fromRevision', false);
        $toSha   = sha1('toRevision', false);

        $this->input->expects(self::any())->method('getOption')->willReturnMap([
            ['from', $fromSha],
            ['to', $toSha],
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
            ->with((string) $this->sourceRepository)
            ->willReturn($this->dependencies);

        $this->compareApi->expects(self::once())->method('__invoke')->willReturn(Changes::empty());

        self::assertSame(0, $this->compare->execute($this->input, $this->output));
    }

    public function testExecuteReturnsNonZeroExitCodeWhenChangesAreDetected() : void
    {
        $fromSha = sha1('fromRevision', false);
        $toSha   = sha1('toRevision', false);

        $this->input->expects(self::any())->method('getOption')->willReturnMap([
            ['from', $fromSha],
            ['to', $toSha],
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
            ->with((string) $this->sourceRepository)
            ->willReturn($this->dependencies);

        $this->compareApi->expects(self::once())->method('__invoke')->willReturn(Changes::fromList(
            Change::added(uniqid('added', true), true)
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

    public function testProvidingMarkdownOptionWritesMarkdownOutput() : void
    {
        $fromSha = sha1('fromRevision', false);
        $toSha   = sha1('toRevision', false);

        $this->input->expects(self::any())->method('getOption')->willReturnMap([
            ['from', $fromSha],
            ['to', $toSha],
            ['format', ['markdown']],
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
            ->method('__invoke')
            ->with((string) $this->sourceRepository)
            ->willReturn($this->dependencies);

        $changeToExpect = uniqid('changeToExpect', true);
        $this->compareApi->expects(self::once())->method('__invoke')->willReturn(Changes::fromList(
            Change::removed($changeToExpect, true)
        ));

        $this->output
            ->expects(self::once())
            ->method('writeln')
            ->willReturnCallback(static function (string $output) use ($changeToExpect) : void {
                self::assertStringContainsString(' [BC] ' . $changeToExpect, $output);
            });

        $this->compare->execute($this->input, $this->output);
    }

    public function testExecuteWithDefaultRevisionsNotProvidedAndNoDetectedTags() : void
    {
        $this->input->expects(self::any())->method('getOption')->willReturnMap([
            ['from', null],
            ['to', 'HEAD'],
        ]);
        $this->input->expects(self::any())->method('getArgument')->willReturnMap([
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

        $this->expectException(AssertionFailedException::class);

        $this->compare->execute($this->input, $this->output);
    }

    /**
     * @dataProvider validVersionCollections
     */
    public function testExecuteWithDefaultRevisionsNotProvided(VersionCollection $versions) : void
    {
        $fromSha       = sha1('fromRevision', false);
        $toSha         = sha1('toRevision', false);
        $pickedVersion = Version::fromString('1.0.0');

        $this->input->expects(self::any())->method('getOption')->willReturnMap([
            ['from', null],
            ['to', 'HEAD'],
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
            ->with((string) $pickedVersion)
            ->willReturn(Revision::fromSha1($fromSha));
        $this->parseRevision->expects(self::at(1))
            ->method('fromStringForRepository')
            ->with('HEAD')
            ->willReturn(Revision::fromSha1($toSha));

        $this->getVersions->expects(self::once())
            ->method('fromRepository')
            ->with(self::callback(function (CheckedOutRepository $checkedOutRepository) : bool {
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
    public function validVersionCollections() : array
    {
        return [
            [new VersionCollection(
                Version::fromString('1.0.0'),
                Version::fromString('1.0.1'),
                Version::fromString('1.0.2')
            ),
            ],
            [new VersionCollection(
                Version::fromString('1.0.0'),
                Version::fromString('1.0.1')
            ),
            ],
            [new VersionCollection(Version::fromString('1.0.0'))],
        ];
    }
}
