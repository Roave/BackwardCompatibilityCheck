<?php
declare(strict_types=1);

namespace Roave\ApiCompare\Git;

use Version\VersionsCollection;

interface GetVersionCollection
{
    public function fromRepository(CheckedOutRepository $repository) : VersionsCollection;
}
