<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Command;

use Assert\Assert;
use Roave\ApiCompare\Comparator;
use Roave\ApiCompare\Factory\DirectoryReflectorFactory;
use Roave\ApiCompare\Formatter\MarkdownPipedToSymfonyConsoleFormatter;
use Roave\ApiCompare\Formatter\SymfonyConsoleTextFormatter;
use Roave\ApiCompare\Git\CheckedOutRepository;
use Roave\ApiCompare\Git\GetVersionCollection;
use Roave\ApiCompare\Git\ParseRevision;
use Roave\ApiCompare\Git\PerformCheckoutOfRevision;
use Roave\ApiCompare\Git\PickVersionFromVersionCollection;
use Roave\ApiCompare\Git\Revision;
use Roave\ApiCompare\Support\ArrayHelpers;
use Roave\ApiCompare\LocateDependencies\LocateDependencies;
use Roave\ApiCompare\LocateDependencies\LocateDependenciesViaComposerInstallation;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function assert;
use function count;
use function getcwd;
use function sprintf;

final class ApiCompare extends Command
{
    /** @var PerformCheckoutOfRevision */
    private $git;

    /** @var DirectoryReflectorFactory */
    private $reflectorFactory;

    /** @var ParseRevision */
    private $parseRevision;

    /** @var GetVersionCollection */
    private $getVersions;

    /** @var PickVersionFromVersionCollection */
    private $pickFromVersion;

    /** @var LocateDependencies */
    private $locateDependencies;

    /** @var Comparator */
    private $comparator;

    /**
     * @throws LogicException
     */
    public function __construct(
        PerformCheckoutOfRevision $git,
        DirectoryReflectorFactory $reflectorFactory,
        ParseRevision $parseRevision,
        GetVersionCollection $getVersions,
        PickVersionFromVersionCollection $pickFromVersion,
        LocateDependencies $locateDependencies,
        Comparator $comparator
    ) {
        parent::__construct();

        $this->git                = $git;
        $this->reflectorFactory   = $reflectorFactory;
        $this->parseRevision      = $parseRevision;
        $this->getVersions        = $getVersions;
        $this->pickFromVersion    = $pickFromVersion;
        $this->locateDependencies = $locateDependencies;
        $this->comparator         = $comparator;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function configure() : void
    {
        $this
            ->setName('api-compare:compare')
            ->setDescription('List comparisons between class APIs')
            ->addOption('from', null, InputOption::VALUE_OPTIONAL)
            ->addOption('to', null, InputOption::VALUE_REQUIRED, '', 'HEAD')
            ->addOption('format', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY)
            ->addArgument(
                'sources-path',
                InputArgument::OPTIONAL,
                'Path to the sources, relative to the repository root',
                'src'
            );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        assert($output instanceof ConsoleOutputInterface, '');
        $stdErr = $output->getErrorOutput();

        // @todo fix flaky assumption about the path of the source repo...
        $sourceRepo = CheckedOutRepository::fromPath(getcwd());

        $fromRevision = $input->hasOption('from')
            ? $this->parseRevisionFromInput($input, $sourceRepo)
            : $this->determineFromRevisionFromRepository($sourceRepo, $stdErr);

        $toRevision  = $this->parseRevision->fromStringForRepository($input->getOption('to'), $sourceRepo);
        $sourcesPath = $input->getArgument('sources-path');

        $stdErr->writeln(sprintf('Comparing from %s to %s...', (string) $fromRevision, (string) $toRevision));

        $fromPath = $this->git->checkout($sourceRepo, $fromRevision);
        $toPath   = $this->git->checkout($sourceRepo, $toRevision);

        try {
            $fromSources = $fromPath . '/' . $sourcesPath;
            $toSources   = $toPath . '/' . $sourcesPath;

            Assert::that($fromSources)->directory();
            Assert::that($toSources)->directory();

            $changes = $this->comparator->compare(
                $this->reflectorFactory->__invoke(
                    $fromPath . '/' . $sourcesPath,
                    new AggregateSourceLocator() // no dependencies
                ),
                $this->reflectorFactory->__invoke(
                    $fromPath . '/' . $sourcesPath,
                    $this->locateDependencies->__invoke((string) $fromPath)
                ),
                $this->reflectorFactory->__invoke(
                    $toPath . '/' . $sourcesPath,
                    $this->locateDependencies->__invoke((string) $toPath)
                )
            );

            (new SymfonyConsoleTextFormatter($stdErr))->write($changes);

            $outputFormats = $input->getOption('format') ?: [];
            Assert::that($outputFormats)->isArray();

            if (ArrayHelpers::stringArrayContainsString('markdown', $outputFormats)) {
                (new MarkdownPipedToSymfonyConsoleFormatter($output))->write($changes);
            }
        } finally {
            $this->git->remove($fromPath);
            $this->git->remove($toPath);
        }

        return count($changes) > 0 ? 2 : 0;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function parseRevisionFromInput(InputInterface $input, CheckedOutRepository $repository) : Revision
    {
        return $this->parseRevision->fromStringForRepository(
            (string) $input->getOption('from'),
            $repository
        );
    }

    private function determineFromRevisionFromRepository(CheckedOutRepository $repository, OutputInterface $output
    ) : Revision {
        $versionString = $this->pickFromVersion->forVersions(
            $this->getVersions->fromRepository($repository)
        )->getVersionString();
        $output->writeln(sprintf('Detected last minor version: %s', $versionString));
        return $this->parseRevision->fromStringForRepository(
            $versionString,
            $repository
        );
    }
}
