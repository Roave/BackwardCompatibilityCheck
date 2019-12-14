<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Version\VersionCollection;

interface GetVersionCollection
{
    public function fromRepository(CheckedOutRepository $repository) : VersionCollection;
}
