<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Version\Version;
use Version\VersionCollection;

interface PickVersionFromVersionCollection
{
    public function forVersions(VersionCollection $versionsCollection) : Version;
}
