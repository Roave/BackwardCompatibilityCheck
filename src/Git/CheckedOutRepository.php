<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Psl;
use Psl\Filesystem;

/** @psalm-immutable */
final class CheckedOutRepository
{
    /** @param non-empty-string $path */
    private function __construct(private readonly string $path)
    {
    }

    /** @param non-empty-string $path */
    public static function fromPath(string $path): self
    {
        Psl\invariant(Filesystem\is_directory($path . '/.git'), 'Directory "%s" is not a GIT repository.', $path);

        return new self($path);
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->path;
    }
}
