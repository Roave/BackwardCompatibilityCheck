<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Psl;
use Psl\Filesystem;

final class CheckedOutRepository
{
    private function __construct(private string $path)
    {
    }

    public static function fromPath(string $path): self
    {
        Psl\invariant(Filesystem\is_directory($path . '/.git'), 'Directory "%s" is not a GIT repository.', $path);

        return new self($path);
    }

    public function __toString(): string
    {
        return $this->path;
    }
}
