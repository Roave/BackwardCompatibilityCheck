<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Psl;
use Psl\Regex;
use Psl\Str;
use Psl\Type;

final class Revision
{
    /** @param non-empty-string $sha1 */
    private function __construct(private string $sha1)
    {
    }

    public static function fromSha1(string $sha1): self
    {
        Psl\invariant(Regex\matches($sha1, '/^[a-zA-Z0-9]{40}$/'), 'Invalid SHA1 hash.');

        return new self(
            Type\non_empty_string()
                ->assert(Str\trim_right($sha1))
        );
    }

    public function __toString(): string
    {
        return $this->sha1;
    }
}
