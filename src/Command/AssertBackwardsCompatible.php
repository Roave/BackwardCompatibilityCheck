<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Command;

use Psl;
use Psl\Env;
use Psl\Iter;
use Psl\Str;
use Psl\Type;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\CompareApi;
use Roave\BackwardCompatibility\Factory\ComposerInstallationReflectorFactory;
use Roave\BackwardCompatibility\Formatter\GithubActionsFormatter;
use Roave\BackwardCompatibility\Formatter\JunitFormatter;
use Roave\BackwardCompatibility\Formatter\MarkdownPipedToSymfonyConsoleFormatter;
use Roave\BackwardCompatibility\Formatter\SymfonyConsoleTextFormatter;
use Roave\BackwardCompatibility\Git\CheckedOutRepository;
use Roave\BackwardCompatibility\Git\GetVersionCollection;
use Roave\BackwardCompatibility\Git\ParseRevision;
use Roave\BackwardCompatibility\Git\PerformCheckoutOfRevision;
use Roave\BackwardCompatibility\Git\PickVersionFromVersionCollection;
use Roave\BackwardCompatibility\LocateDependencies\LocateDependencies;
use Roave\BackwardCompatibility\VersionConstraint\StableVersionConstraint;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AssertBackwardsCompatible extends Command
{
    /** @throws LogicException */
    public function __construct(
        private PerformCheckoutOfRevision $git,
        private ComposerInstallationReflectorFactory $makeComposerInstallationReflector,
        private ParseRevision $parseRevision,
        private GetVersionCollection $getVersions,
        private PickVersionFromVersionCollection $pickFromVersion,
        private LocateDependencies $locateDependencies,
        private CompareApi $compareApi,
    ) {
        parent::__construct();
    }

    /** @throws InvalidArgumentException */
    protected function configure(): void
    {
        $this
            ->setName('roave-backwards-compatibility-check:assert-backwards-compatible')
            ->setDescription('Verifies that the revision being compared with "from" does not introduce any BC (backwards-incompatible) changes')
            ->addOption(
                'from',
                null,
                InputOption::VALUE_OPTIONAL,
                'Git reference for the base version of the library, which is considered "stable"',
            )
            ->addOption(
                'to',
                null,
                InputOption::VALUE_REQUIRED,
                'Git reference for the new version of the library, which is verified against "from" for BC breaks',
                'HEAD',
            )
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Currently supports "console", "markdown", "github-actions" or "junit"',
                ['console'],
                ['console', 'markdown', 'github-actions', 'junit'],
            )
            ->addOption(
                'install-development-dependencies',
                null,
                InputOption::VALUE_NONE,
                'Whether to also install "require-dev" dependencies too',
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
defined "autoload" paths in your `composer.json` definition.

Once completed, it will print out the results to `STDERR`
and terminate with `3` if breaking changes were detected.

If you want to produce `STDOUT` output, then please use the
`--format` flag.
USAGE,
            );
    }

    /** @throws InvalidArgumentException */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = Type\instance_of(ConsoleOutputInterface::class)->assert($output);
        $stdErr = $output->getErrorOutput();

        // @todo fix flaky assumption about the path of the source repo...
        $sourceRepo = CheckedOutRepository::fromPath(Env\current_dir());

        if ($input->getOption('from') !== null) {
            $maybeRevision = Type\string()->coerce($input->getOption('from'));
        } else {
            $versions = $this->getVersions->fromRepository($sourceRepo);

            Psl\invariant(Iter\count($versions) >= 1, 'Could not detect any released versions for the given repository');

            $stableVersions = $versions->matching(new StableVersionConstraint());

            if ($stableVersions->isEmpty()) {
                $stdErr->writeln(Str\format('Your library does not have any stable versions. Therefore cannot have BC breaks.'));

                return Command::SUCCESS;
            }

            $maybeRevision = $this->pickFromVersion->forVersions($versions)->toString();

            $stdErr->writeln(Str\format('Detected last minor version: %s', $maybeRevision));
        }

        $fromRevision = $this->parseRevision->fromStringForRepository($maybeRevision, $sourceRepo);

        $to = Type\string()->coerce($input->getOption('to'));

        $includeDevelopmentDependencies = Type\bool()->coerce($input->getOption('install-development-dependencies'));

        $toRevision = $this->parseRevision->fromStringForRepository($to, $sourceRepo);

        $stdErr->writeln(Str\format(
            'Comparing from %s to %s...',
            Type\string()->coerce($fromRevision),
            Type\string()->coerce($toRevision),
        ));

        $fromPath = $this->git->checkout($sourceRepo, $fromRevision);
        $toPath   = $this->git->checkout($sourceRepo, $toRevision);

        try {
            $changes = ($this->compareApi)(
                ($this->makeComposerInstallationReflector)(
                    $fromPath->__toString(),
                    new AggregateSourceLocator(), // no dependencies
                ),
                ($this->makeComposerInstallationReflector)(
                    $fromPath->__toString(),
                    ($this->locateDependencies)($fromPath->__toString(), $includeDevelopmentDependencies),
                ),
                ($this->makeComposerInstallationReflector)(
                    $toPath->__toString(),
                    ($this->locateDependencies)($toPath->__toString(), $includeDevelopmentDependencies),
                ),
            );

            $formatters = [
                'console'        => new SymfonyConsoleTextFormatter($stdErr),
                'markdown'       => new MarkdownPipedToSymfonyConsoleFormatter($output),
                'github-actions' => new GithubActionsFormatter($output, $toPath),
                'junit'          => new JunitFormatter($output, $toPath),
            ];

            foreach (
                Type\vec(Type\union(
                    Type\literal_scalar('console'),
                    Type\literal_scalar('markdown'),
                    Type\literal_scalar('github-actions'),
                    Type\literal_scalar('junit'),
                ))->coerce((array) $input->getOption('format')) as $format
            ) {
                $formatters[$format]->write($changes);
            }
        } finally {
            $this->git->remove($fromPath);
            $this->git->remove($toPath);
        }

        return $this->printOutcomeAndExit($changes, $stdErr);
    }

    private function printOutcomeAndExit(Changes $changes, OutputInterface $stdErr): int
    {
        $hasBcBreaks = Iter\count($changes);

        if ($hasBcBreaks) {
            $stdErr->writeln(Str\format('<error>%s backwards-incompatible changes detected</error>', $hasBcBreaks));
        } else {
            $stdErr->writeln('<info>No backwards-incompatible changes detected</info>', $hasBcBreaks);
        }

        return $hasBcBreaks ? 3 : 0;
    }
}
