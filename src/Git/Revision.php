<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Psl\Str;
use Psl\Regex;
use Psl;

final class Revision
{
    private string $sha1;

    private function __construct()
    {
    }

    public static function fromSha1(string $sha1): self
    {
        Psl\invariant(Regex\matches($sha1, '/^[a-zA-Z0-9]{40}$/'), 'Invalid SHA1 hash.');

        $instance       = new self();
        $instance->sha1 = Str\trim_right($sha1);

        return $instance;
    }

    public function __toString(): string
    {
        return $this->sha1;
    }
}
