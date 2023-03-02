<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Configuration;

use Roave\BackwardCompatibility\Baseline;

/** @psalm-immutable */
final class Configuration
{
    private function __construct(
        public readonly Baseline $baseline,
        public readonly string|null $filename,
    ) {
    }

    public static function default(): self
    {
        return new self(Baseline::empty(), null);
    }

    public static function fromFile(Baseline $baseline, string $filename): self
    {
        return new self($baseline, $filename);
    }
}
