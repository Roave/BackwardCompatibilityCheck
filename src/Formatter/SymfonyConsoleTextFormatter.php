<?php
declare(strict_types=1);

namespace Roave\ApiCompare\Formatter;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Symfony\Component\Console\Output\OutputInterface;

final class SymfonyConsoleTextFormatter implements OutputFormatter
{
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function write(Changes $changes) : void
    {
        /** @var Change $change */
        foreach ($changes as $change) {
            $this->output->writeln((string)$change);
        }
    }
}
