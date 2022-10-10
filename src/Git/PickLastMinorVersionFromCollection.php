<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Psl;
use Psl\Type;
use Version\Comparison\Constraint\CompositeConstraint;
use Version\Comparison\Constraint\Constraint;
use Version\Comparison\Constraint\OperationConstraint;
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

        $lastVersion = $versionsSortedDescending->first();

        $matchingMinorVersions = $stableVersions
            ->matching(CompositeConstraint::and(
                Type\instance_of(OperationConstraint::class)
                    ->coerce(OperationConstraint::lessOrEqualTo($lastVersion)),
                Type\instance_of(OperationConstraint::class)
                    ->coerce(OperationConstraint::greaterOrEqualTo(
                        Type\instance_of(Version::class)
                            ->coerce(Version::fromString($lastVersion->getMajor() . '.' . $lastVersion->getMinor() . '.0')),
                    )),
            ))
            ->sortedAscending();

        return $matchingMinorVersions->first();
    }
}
