<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Git;

use Assert\Assert;

final class CheckedOutRepository
{
    /** @var string */
    private $path;

    private function __construct()
    {
    }

    public static function fromPath(string $path) : self
    {
        Assert::that($path)->directory();
        Assert::that($path . '/.git')->directory();
        $instance       = new self();
        $instance->path = $path;
        return $instance;
    }

    public function __toString() : string
    {
        return $this->path;
    }
}
