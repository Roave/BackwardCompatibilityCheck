<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Formatter;

use Psl\Str;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Git\CheckedOutRepository;
use Symfony\Component\Console\Output\OutputInterface;

use function Psl\Json\encode;

/** @internal */
final class JsonFormatter implements OutputFormatter
{
    public function __construct(
        private readonly OutputInterface $output,
        private CheckedOutRepository $basePath,
    ) {
    }

    public function write(Changes $changes): void
    {
        $basePath = $this->basePath->__toString() . '/';
        $result   = [];

        foreach ($changes as $change) {
            $result[] = [
                'description' => $change->description,
                'path' => $change->file === null ? null : Str\replace($change->file, $basePath, ''),
                'line' => $change->line,
                'column' => $change->column,
            ];
        }

        $this->output->writeln(encode(['errors' => $result]));
    }
}
