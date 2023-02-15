<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility;

use function array_values;
use function preg_match;

/** @psalm-immutable */
final class Baseline
{
    /** @psalm-param list<string> $ignoredChanges */
    private function __construct(private readonly array $ignoredChanges = [])
    {
    }

    public static function empty(): self
    {
        return new self();
    }

    public static function fromList(string ...$ignoredChanges): self
    {
        return new self(array_values($ignoredChanges));
    }

    public function ignores(Change $change): bool
    {
        $changeDescription = $change->__toString();

        foreach ($this->ignoredChanges as $ignoredChangeRegex) {
            if (preg_match($ignoredChangeRegex, $changeDescription) === 1) {
                return true;
            }
        }

        return false;
    }
}
