<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Formatter;

use Psl\Str;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Git\CheckedOutRepository;
use Symfony\Component\Console\CI\GithubActionReporter;
use Symfony\Component\Console\Output\OutputInterface;

/** @internal */
final class GithubActionsFormatter implements OutputFormatter
{
    public function __construct(
        private OutputInterface $output,
        private CheckedOutRepository $basePath,
    ) {
    }

    public function write(Changes $changes): void
    {
        $reporter = new GithubActionReporter($this->output);
        $basePath = $this->basePath->__toString() . '/';

        foreach ($changes as $change) {
            $reporter->error(
                $change->description,
                $change->file === null
                    ? null
                    : Str\replace($change->file, $basePath, ''),
                $change->line,
                $change->column,
            );
        }
    }
}
