<?php
declare(strict_types=1);

namespace Roave\ApiCompare\Git;

use Assert\Assert;

final class Revision
{
    /** @var string */
    private $sha1;

    private function __construct()
    {
    }

    public static function fromSha1(string $sha1) : self
    {
        Assert::that($sha1)->regex('/^[a-zA-Z0-9]{40}$/');
        $instance = new self();
        $instance->sha1 = $sha1;
        return $instance;
    }

    public function __toString() : string
    {
        return $this->sha1;
    }
}
