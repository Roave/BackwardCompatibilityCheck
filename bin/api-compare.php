<?php
declare(strict_types=1);

use Assert\Assert;
use Roave\ApiCompare\Comparator;
use Roave\ApiCompare\Factory\DirectoryReflectorFactory;
use Roave\ApiCompare\Formatter\SymfonyConsoleTextFormatter;
use Roave\ApiCompare\Git\CheckedOutRepository;
use Roave\ApiCompare\Git\GitCheckoutRevisionToTemporaryPath;
use Roave\ApiCompare\Git\Revision;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

$foundAutoload = false;
foreach ([__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../autoload.php'] as $autoload) {
    if (file_exists($autoload)) {
        /** @noinspection PhpIncludeInspection */
        require $autoload;
        $foundAutoload = true;
        break;
    }
}

if (!$foundAutoload) {
    throw new \RuntimeException('Could not find Composer autoload.php');
}

$application = new Application();
$application->add(new class extends Command {
    /**
     *
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
    protected function execute(InputInterface $input, OutputInterface $output) : void
    {
        $reflectorFactory = new DirectoryReflectorFactory();
        $git = new GitCheckoutRevisionToTemporaryPath();

        // @todo fix flaky assumption about the path of the source repo...
        $sourceRepo = CheckedOutRepository::fromPath(getcwd());

        $fromPath = $git->checkout($sourceRepo, Revision::fromSha1($input->getArgument('from')));
        $toPath = $git->checkout($sourceRepo, Revision::fromSha1($input->getArgument('to')));

        // @todo fix hard-coded /src/ addition...
        try {
            (new SymfonyConsoleTextFormatter($output))->write(
                (new Comparator())->compare(
                    $reflectorFactory->__invoke((string)$fromPath . '/src/'),
                    $reflectorFactory->__invoke((string)$toPath . '/src/')
                )
            );
        } finally {
            $git->remove($fromPath);
            $git->remove($toPath);
        }
    }
});
$application->setDefaultCommand('api-compare:compare');

/** @noinspection PhpUnhandledExceptionInspection */
$application->run();
