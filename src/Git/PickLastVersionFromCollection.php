<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Psl;
use Version\Comparison\Constraint\Constraint;
use Version\Version;
use Version\VersionCollection;

final class PickLastVersionFromCollection implements PickVersionFromVersionCollection
{
    public function forVersions(VersionCollection $versionsCollection): Version
    {
        Psl\invariant(! $versionsCollection->isEmpty(), 'Cannot determine latest version from an empty collection');

        $stableVersions = $versionsCollection->matching(new class implements Constraint {
            public function assert(Version $version): bool
            {
                return ! $version->isPreRelease();
            }
        });

        return $stableVersions->sortedDescending()->first();
    }
}
