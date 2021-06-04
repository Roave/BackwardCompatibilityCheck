<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Psl;
use Psl\Filesystem;

final class CheckedOutRepository
{
    private string $path;

    private function __construct()
    {
    }

    public static function fromPath(string $path): self
    {
        Psl\invariant(Filesystem\is_directory($path . '/.git'), 'Directory "%s" is not a GIT repository.', $path);

        $instance       = new self();
        $instance->path = $path;

        return $instance;
    }

    public function __toString(): string
    {
        return $this->path;
    }
}
