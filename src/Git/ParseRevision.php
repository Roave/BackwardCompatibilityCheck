<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

interface ParseRevision
{
    public function fromStringForRepository(string $something, CheckedOutRepository $repository): Revision;
}
