<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Formatter;

use Psl\Str;
use Psl\Vec;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Symfony\Component\Console\Output\OutputInterface;

final class MarkdownPipedToSymfonyConsoleFormatter implements OutputFormatter
{
    public function __construct(private OutputInterface $output)
    {
    }

    public function write(Changes $changes): void
    {
        $arrayOfChanges = Vec\values($changes);

        $this->output->writeln(
            "# Added\n"
            . Str\join($this->convertFilteredChangesToMarkdownBulletList(
                static function (Change $change): bool {
                    return $change->isAdded();
                },
                ...$arrayOfChanges,
            ), '')
            . "\n# Changed\n"
            . Str\join($this->convertFilteredChangesToMarkdownBulletList(
                static function (Change $change): bool {
                    return $change->isChanged();
                },
                ...$arrayOfChanges,
            ), '')
            . "\n# Removed\n"
            . Str\join($this->convertFilteredChangesToMarkdownBulletList(
                static function (Change $change): bool {
                    return $change->isRemoved();
                },
                ...$arrayOfChanges,
            ), '')
            . "\n# Skipped\n"
            . Str\join($this->convertFilteredChangesToMarkdownBulletList(
                static function (Change $change): bool {
                    return $change->isSkipped();
                },
                ...$arrayOfChanges,
            ), ''),
        );
    }

    /**
     * @param callable(Change): bool $filterFunction
     *
     * @return list<string>
     */
    private function convertFilteredChangesToMarkdownBulletList(callable $filterFunction, Change ...$changes): array
    {
        return Vec\map(
            Vec\filter($changes, $filterFunction),
            static function (Change $change): string {
                return ' - ' . Str\replace_every(Str\trim($change->__toString()), ['ADDED: ' => '', 'CHANGED: ' => '', 'REMOVED: ' => '', 'SKIPPED: ' => '']) . "\n";
            },
        );
    }
}
