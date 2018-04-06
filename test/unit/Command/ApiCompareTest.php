<?php
declare(strict_types=1);

namespace RoaveTest\ApiCompare\Command;

use PHPUnit\Framework\MockObject\MockObject;
use Roave\ApiCompare\Command\ApiCompare;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Factory\DirectoryReflectorFactory;
use Roave\ApiCompare\Git\CheckedOutRepository;
use Roave\ApiCompare\Git\PerformCheckoutOfRevision;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Roave\ApiCompare\Command\ApiCompare
 */
final class ApiCompareTest extends TestCase
{
    public function testExecute() : void
    {
        $sourceRepository = CheckedOutRepository::fromPath(realpath(__DIR__ . '/../../../'));

        $fromSha = sha1('fromRevision', false);
        $toSha = sha1('toRevision', false);

        /** @var InputInterface|MockObject $input */
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::at(0))->method('getArgument')->with('from')->willReturn($fromSha);
        $input->expects(self::at(1))->method('getArgument')->with('to')->willReturn($toSha);
        /** @var OutputInterface|MockObject $output */
        $output = $this->createMock(OutputInterface::class);
        /** @var PerformCheckoutOfRevision|MockObject $git */
        $git = $this->createMock(PerformCheckoutOfRevision::class);
        $git->expects(self::at(0))
            ->method('checkout')
            ->with($sourceRepository, $fromSha)
            ->willReturn($sourceRepository);
        $git->expects(self::at(1))
            ->method('checkout')
            ->with($sourceRepository, $toSha)
            ->willReturn($sourceRepository);
        $git->expects(self::at(2))
            ->method('remove')
            ->with($sourceRepository);
        $git->expects(self::at(3))
            ->method('remove')
            ->with($sourceRepository);

        $command = new ApiCompare($git, new DirectoryReflectorFactory());

        chdir((string)$sourceRepository);
        $command->execute($input, $output);
    }
}
