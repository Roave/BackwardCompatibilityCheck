<?php
declare(strict_types=1);

namespace Roave\ApiCompare\Command;

use Roave\ApiCompare\Comparator;
use Roave\ApiCompare\Factory\DirectoryReflectorFactory;
use Roave\ApiCompare\Formatter\SymfonyConsoleTextFormatter;
use Roave\ApiCompare\Git\CheckedOutRepository;
use Roave\ApiCompare\Git\PerformCheckoutOfRevision;
use Roave\ApiCompare\Git\Revision;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ApiCompare extends Command
{
    /** @var PerformCheckoutOfRevision */
    private $git;

    /**
     * @var DirectoryReflectorFactory
     */
    private $reflectorFactory;

    /**
     * @param PerformCheckoutOfRevision $git
     * @param DirectoryReflectorFactory $reflectorFactory
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(
        PerformCheckoutOfRevision $git,
        DirectoryReflectorFactory $reflectorFactory
    ) {
        parent::__construct();
        $this->git = $git;
        $this->reflectorFactory = $reflectorFactory;
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure() : void
    {
        $this
            ->setName('api-compare:compare')
            ->setDescription('List comparisons between class APIs')
            ->addArgument('from', InputArgument::REQUIRED)
            ->addArgument('to', InputArgument::REQUIRED)
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Roave\BetterReflection\SourceLocator\Exception\InvalidFileInfo
     * @throws \Roave\BetterReflection\SourceLocator\Exception\InvalidDirectory
     */
    public function execute(InputInterface $input, OutputInterface $output) : void
    {
        // @todo fix flaky assumption about the path of the source repo...
        $sourceRepo = CheckedOutRepository::fromPath(getcwd());

        $fromPath = $this->git->checkout($sourceRepo, Revision::fromSha1($input->getArgument('from')));
        $toPath = $this->git->checkout($sourceRepo, Revision::fromSha1($input->getArgument('to')));

        // @todo fix hard-coded /src/ addition...
        try {
            (new SymfonyConsoleTextFormatter($output))->write(
                (new Comparator())->compare(
                    $this->reflectorFactory->__invoke((string)$fromPath . '/src/'),
                    $this->reflectorFactory->__invoke((string)$toPath . '/src/')
                )
            );
        } finally {
            $this->git->remove($fromPath);
            $this->git->remove($toPath);
        }
    }
}
