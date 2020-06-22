<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Webmozart\Assert\Assert;

final class CheckedOutRepository
{
    private string $path;

    private function __construct()
    {
    }

    public static function fromPath(string $path): self
    {
        Assert::directory($path . '/.git');

        $instance       = new self();
        $instance->path = $path;

        return $instance;
    }

    public function __toString(): string
    {
        return $this->path;
    }
}
