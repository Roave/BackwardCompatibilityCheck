<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Command;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Command\ApiCompare;
use Roave\ApiCompare\Comparator;
use Roave\ApiCompare\Factory\DirectoryReflectorFactory;
use Roave\ApiCompare\Git\CheckedOutRepository;
use Roave\ApiCompare\Git\GetVersionCollection;
use Roave\ApiCompare\Git\ParseRevision;
use Roave\ApiCompare\Git\PerformCheckoutOfRevision;
use Roave\ApiCompare\Git\PickVersionFromVersionCollection;
use Roave\ApiCompare\Git\Revision;
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
 * @covers \Roave\ApiCompare\Command\ApiCompare
 */
final class ApiCompareTest extends TestCase
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

    /** @var Comparator|MockObject */
    private $comparator;

    /** @var ApiCompare */
    private $compare;

    public function setUp() : void
    {
        $this->sourceRepository = CheckedOutRepository::fromPath(realpath(__DIR__ . '/../../../'));
        chdir((string) $this->sourceRepository);

        $this->input   = $this->createMock(InputInterface::class);
        $this->output  = $this->createMock(ConsoleOutputInterface::class);
        $this->stdErr  = $this->createMock(OutputInterface::class);
        $this->output->expects(self::any())->method('getErrorOutput')->willReturn($this->stdErr);

        $this->performCheckout = $this->createMock(PerformCheckoutOfRevision::class);
        $this->parseRevision   = $this->createMock(ParseRevision::class);
        $this->getVersions     = $this->createMock(GetVersionCollection::class);
        $this->pickVersion     = $this->createMock(PickVersionFromVersionCollection::class);
        $this->comparator      = $this->createMock(Comparator::class);
        $this->compare         = new ApiCompare(
            $this->performCheckout,
            new DirectoryReflectorFactory(),
            $this->parseRevision,
            $this->getVersions,
            $this->pickVersion,
            $this->comparator
        );
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

        $this->comparator->expects(self::once())->method('compare')->willReturn(Changes::new());

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

        $this->comparator->expects(self::once())->method('compare')->willReturn(Changes::fromArray([
            Change::added(uniqid('added', true), true),
            Change::removed(uniqid('removed', true), true),
        ]));

        self::assertSame(2, $this->compare->execute($this->input, $this->output));
    }

    public function testProvidingMarkdownOptionWritesMarkdownOutput() : void
    {
        $fromSha = sha1('fromRevision', false);
        $toSha = sha1('toRevision', false);

        $this->input->expects(self::any())->method('hasOption')->willReturn(true);
        $this->input->expects(self::any())->method('getOption')->willReturnMap([
            ['from', $fromSha],
            ['to', $toSha],
            ['format', ['markdown']]
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

        $changeToExpect = uniqid('changeToExpect', true);
        $this->comparator->expects(self::once())->method('compare')->willReturn(Changes::fromArray([
            Change::removed($changeToExpect, true),
        ]));

        $this->compare->execute($this->input, $this->output);


        $this->output->expects(self::any())
            ->method('writeln')
            ->willReturnCallback(function (string $output) use ($changeToExpect) {
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

        $this->comparator->expects(self::once())->method('compare')->willReturn(Changes::new());

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
