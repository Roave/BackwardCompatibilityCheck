<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Psl;
use Version\Comparison\Constraint\Constraint;
use Version\Version;
use Version\VersionCollection;

final class PickLastMinorVersionFromCollection implements PickVersionFromVersionCollection
{
    public function forVersions(VersionCollection $versionsCollection): Version
    {
        Psl\invariant(! $versionsCollection->isEmpty(), 'Cannot determine latest minor version from an empty collection');

        $stableVersions = $versionsCollection->matching(new class implements Constraint {
            public function assert(Version $version): bool
            {
                return ! $version->isPreRelease();
            }
        });

        $versionsSortedDescending = $stableVersions->sortedDescending();

        return $versionsSortedDescending->first();
    }
}
