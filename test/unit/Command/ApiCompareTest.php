<?php
declare(strict_types=1);

namespace RoaveTest\ApiCompare\Command;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Roave\ApiCompare\Command\ApiCompare;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Factory\DirectoryReflectorFactory;
use Roave\ApiCompare\Git\CheckedOutRepository;
use Roave\ApiCompare\Git\GetVersionCollection;
use Roave\ApiCompare\Git\ParseRevision;
use Roave\ApiCompare\Git\PerformCheckoutOfRevision;
use Roave\ApiCompare\Git\PickVersionFromVersionCollection;
use Roave\ApiCompare\Git\Revision;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Roave\ApiCompare\Command\ApiCompare
 */
final class ApiCompareTest extends TestCase
{
    /** @var CheckedOutRepository */
    private $sourceRepository;

    /** @var InputInterface|MockObject */
    private $input;

    /** @var OutputInterface|MockObject */
    private $output;

    /** @var PerformCheckoutOfRevision|MockObject */
    private $performCheckout;

    /** @var ParseRevision|MockObject */
    private $parseRevision;

    /** @var GetVersionCollection|MockObject */
    private $getVersions;

    /** @var PickVersionFromVersionCollection|MockObject */
    private $pickVersion;

    /** @var ApiCompare */
    private $compare;

    public function setUp() : void
    {
        $this->sourceRepository = CheckedOutRepository::fromPath(realpath(__DIR__ . '/../../../'));
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
        $this->performCheckout = $this->createMock(PerformCheckoutOfRevision::class);
        $this->parseRevision = $this->createMock(ParseRevision::class);
        $this->getVersions = $this->createMock(GetVersionCollection::class);
        $this->pickVersion = $this->createMock(PickVersionFromVersionCollection::class);
        $this->compare = new ApiCompare(
            $this->performCheckout,
            new DirectoryReflectorFactory(),
            $this->parseRevision,
            $this->getVersions,
            $this->pickVersion
        );
    }

    public function testExecuteWhenRevisionsAreProvidedAsOptions() : void
    {
        $fromSha = sha1('fromRevision', false);
        $toSha = sha1('toRevision', false);

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

        chdir((string)$this->sourceRepository);

        $this->compare->execute($this->input, $this->output);
    }

    public function testExecuteFailsIfCheckedOutRepositoryDoesNotExist() : void
    {
        $fromSha = sha1('fromRevision', false);
        $toSha = sha1('toRevision', false);

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

        chdir((string)$this->sourceRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->compare->execute($this->input, $this->output);
    }
}
