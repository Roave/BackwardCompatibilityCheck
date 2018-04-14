<?php
declare(strict_types=1);

namespace Roave\ApiCompare\Git;

use Version\Version;
use Version\VersionsCollection;

interface PickVersionFromVersionCollection
{
    public function forVersions(VersionsCollection $versionsCollection) : Version;
}
