<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Command;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Command\AssertBackwardsCompatible;
use Roave\BackwardCompatibility\Comparator;
use Roave\BackwardCompatibility\Factory\DirectoryReflectorFactory;
use Roave\BackwardCompatibility\Git\CheckedOutRepository;
use Roave\BackwardCompatibility\Git\GetVersionCollection;
use Roave\BackwardCompatibility\Git\ParseRevision;
use Roave\BackwardCompatibility\Git\PerformCheckoutOfRevision;
use Roave\BackwardCompatibility\Git\PickVersionFromVersionCollection;
use Roave\BackwardCompatibility\Git\Revision;
use Roave\BackwardCompatibility\LocateDependencies\LocateDependencies;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Version\Version;
use Version\VersionsCollection;
use function chdir;
use function realpath;
use function sha1;
use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\Command\AssertBackwardsCompatible
 */
final class AssertBackwardsCompatibleTest extends TestCase
{
    /** @var CheckedOutRepository */
    private $sourceRepository;

    /** @var InputInterface|MockObject */
    private $input;

    /** @var ConsoleOutputInterface|MockObject */
    private $output;

    /** @var OutputInterface|MockObject */
    private $stdErr;

    /** @var PerformCheckoutOfRevision|MockObject */
    private $performCheckout;

    /** @var ParseRevision|MockObject */
    private $parseRevision;

    /** @var GetVersionCollection|MockObject */
    private $getVersions;

    /** @var PickVersionFromVersionCollection|MockObject */
    private $pickVersion;

    /** @var LocateDependencies|MockObject */
    private $locateDependencies;

    /** @var Comparator|MockObject */
    private $comparator;

    /** @var AggregateSourceLocator */
    private $dependencies;

    /** @var AssertBackwardsCompatible */
    private $compare;

    public function setUp() : void
    {
        $this->sourceRepository = CheckedOutRepository::fromPath(realpath(__DIR__ . '/../../../'));
        chdir((string) $this->sourceRepository);

        $this->input              = $this->createMock(InputInterface::class);
        $this->output             = $this->createMock(ConsoleOutputInterface::class);
        $this->stdErr             = $this->createMock(OutputInterface::class);
        $this->performCheckout    = $this->createMock(PerformCheckoutOfRevision::class);
        $this->parseRevision      = $this->createMock(ParseRevision::class);
        $this->getVersions        = $this->createMock(GetVersionCollection::class);
        $this->pickVersion        = $this->createMock(PickVersionFromVersionCollection::class);
        $this->locateDependencies = $this->createMock(LocateDependencies::class);
        $this->dependencies       = new AggregateSourceLocator();
        $this->comparator         = $this->createMock(Comparator::class);
        $this->compare            = new AssertBackwardsCompatible(
            $this->performCheckout,
            new DirectoryReflectorFactory((new BetterReflection())->astLocator()),
            $this->parseRevision,
            $this->getVersions,
            $this->pickVersion,
            $this->locateDependencies,
            $this->comparator
        );

        $this
            ->output
            ->expects(self::any())
            ->method('getErrorOutput')
            ->willReturn($this->stdErr);
    }

    public function testExecuteWhenRevisionsAreProvidedAsOptions() : void
    {
        $fromSha = sha1('fromRevision', false);
        $toSha   = sha1('toRevision', false);

        $this->input->expects(self::any())->method('hasOption')->willReturn(true);
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

        $this->comparator->expects(self::once())->method('compare')->willReturn(Changes::empty());

        self::assertSame(0, $this->compare->execute($this->input, $this->output));
    }

    public function testExecuteReturnsNonZeroExitCodeWhenChangesAreDetected() : void
    {
        $fromSha = sha1('fromRevision', false);
        $toSha   = sha1('toRevision', false);

        $this->input->expects(self::any())->method('hasOption')->willReturn(true);
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

        $this->comparator->expects(self::once())->method('compare')->willReturn(Changes::fromList(
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

        $this->input->expects(self::any())->method('hasOption')->willReturn(true);
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
            ->expects(self::any())
            ->method('__invoke')
            ->with((string) $this->sourceRepository)
            ->willReturn($this->dependencies);

        $changeToExpect = uniqid('changeToExpect', true);
        $this->comparator->expects(self::once())->method('compare')->willReturn(Changes::fromList(
            Change::removed($changeToExpect, true)
        ));

        $this->compare->execute($this->input, $this->output);

        $this->output->expects(self::any())
            ->method('writeln')
            ->willReturnCallback(function (string $output) use ($changeToExpect) : void {
                self::assertContains($changeToExpect, $output);
            });
    }

    public function testExecuteWithDefaultRevisionsNotProvided() : void
    {
        $fromSha       = sha1('fromRevision', false);
        $toSha         = sha1('toRevision', false);
        $versions      = VersionsCollection::fromArray(['1.0.0', '1.0.1']);
        $pickedVersion = Version::fromString('1.0.0');

        $this->input->expects(self::any())->method('hasOption')->willReturn(false);
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

        $this->comparator->expects(self::once())->method('compare')->willReturn(Changes::empty());

        self::assertSame(0, $this->compare->execute($this->input, $this->output));
    }

    public function testExecuteFailsIfCheckedOutRepositoryDoesNotExist() : void
    {
        $fromSha = sha1('fromRevision', false);
        $toSha   = sha1('toRevision', false);

        $this->input->expects(self::any())->method('hasOption')->willReturn(true);
        $this->input->expects(self::any())->method('getOption')->willReturnMap([
            ['from', $fromSha],
            ['to', $toSha],
        ]);
        $this->input->expects(self::any())->method('getArgument')->willReturnMap([
            ['sources-path', uniqid('src', true)],
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

        $this->comparator->expects(self::never())->method('compare');

        $this->expectException(InvalidArgumentException::class);
        $this->compare->execute($this->input, $this->output);
    }
}
