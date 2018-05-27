<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Command;

use Assert\Assert;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\CompareApi;
use Roave\BackwardCompatibility\Factory\DirectoryReflectorFactory;
use Roave\BackwardCompatibility\Formatter\MarkdownPipedToSymfonyConsoleFormatter;
use Roave\BackwardCompatibility\Formatter\SymfonyConsoleTextFormatter;
use Roave\BackwardCompatibility\Git\CheckedOutRepository;
use Roave\BackwardCompatibility\Git\GetVersionCollection;
use Roave\BackwardCompatibility\Git\ParseRevision;
use Roave\BackwardCompatibility\Git\PerformCheckoutOfRevision;
use Roave\BackwardCompatibility\Git\PickVersionFromVersionCollection;
use Roave\BackwardCompatibility\Git\Revision;
use Roave\BackwardCompatibility\LocateDependencies\LocateDependencies;
use Roave\BackwardCompatibility\Support\ArrayHelpers;
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

final class AssertBackwardsCompatible extends Command
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

    /** @var CompareApi */
    private $compareApi;

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
        CompareApi $compareApi
    ) {
        parent::__construct();

        $this->git                = $git;
        $this->reflectorFactory   = $reflectorFactory;
        $this->parseRevision      = $parseRevision;
        $this->getVersions        = $getVersions;
        $this->pickFromVersion    = $pickFromVersion;
        $this->locateDependencies = $locateDependencies;
        $this->compareApi         = $compareApi;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function configure() : void
    {
        $this
            ->setName('roave-backwards-compatibility-check:assert-backwards-compatible')
            ->setDescription('Verifies that the revision being compared with "from" does not introduce any BC (backwards-incompatible) changes')
            ->addOption(
                'from',
                null,
                InputOption::VALUE_OPTIONAL,
                'Git reference for the base version of the library, which is considered "stable"'
            )
            ->addOption(
                'to',
                null,
                InputOption::VALUE_REQUIRED,
                'Git reference for the new version of the library, which is verified against "from" for BC breaks',
                'HEAD'
            )
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Currently only supports "markdown"'
            )
            ->addArgument(
                'sources-path',
                InputArgument::OPTIONAL,
                'Path to the sources, relative to the repository root',
                'src'
            )
            ->addUsage(
                <<<'USAGE'


Without arguments, this command will attempt to detect the 
latest stable git tag ("release", according to this tool)
of the repository in your CWD (current working directory),
and will use it as baseline for the defined API.

It will then create two clones of the repository: one at
the "release" version, and one at the version specified
via `--to` ("HEAD" by default).

It will then install all required dependencies in both copies
and compare the APIs, looking for breaking changes in the
given `<sources-path>` ("src" by default).

Once completed, it will print out the results to `STDERR`
and terminate with `3` if breaking changes were detected.

If you want to produce `STDOUT` output, then please use the
`--format` flag.
USAGE
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

        $fromRevision = $input->hasParameterOption('from')
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

            $changes = $this->compareApi->__invoke(
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

        return $this->printOutcomeAndExit($changes, $stdErr);
    }

    private function printOutcomeAndExit(Changes $changes, OutputInterface $stdErr) : int
    {
        $hasBcBreaks = count($changes);

        if ($hasBcBreaks) {
            $stdErr->writeln(sprintf('<error>%s backwards-incompatible changes detected</error>', $hasBcBreaks));
        } else {
            $stdErr->writeln('<info>No backwards-incompatible changes detected</info>', $hasBcBreaks);
        }

        return $hasBcBreaks ? 3 : 0;
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

    private function determineFromRevisionFromRepository(
        CheckedOutRepository $repository,
        OutputInterface $output
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
