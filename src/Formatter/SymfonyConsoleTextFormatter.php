<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Formatter;

use Roave\BackwardCompatibility\Changes;
use Symfony\Component\Console\Output\OutputInterface;

final class SymfonyConsoleTextFormatter implements OutputFormatter
{
    public function __construct(private OutputInterface $output)
    {
    }

    public function write(Changes $changes): void
    {
        foreach ($changes as $change) {
            $this->output->writeln($change->__toString());
        }
    }
}
