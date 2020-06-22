<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;
use Version\Comparison\Constraint\CompositeConstraint;
use Version\Comparison\Constraint\Constraint;
use Version\Comparison\Constraint\OperationConstraint;
use Version\Version;
use Version\VersionCollection;
use Webmozart\Assert\Assert;

final class PickLastMinorVersionFromCollection implements PickVersionFromVersionCollection
{
    /**
     * {@inheritDoc}
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    public function forVersions(VersionCollection $versions): Version
    {
        Assert::minCount($versions, 1, 'Cannot determine latest minor version from an empty collection');

        $stableVersions = $versions->matching(new class implements Constraint {
            public function assert(Version $version): bool
            {
                return ! $version->isPreRelease();
            }
        });

        $versionsSortedDescending = $stableVersions->sortedDescending();

        $lastVersion = $versionsSortedDescending->first();

        $matchingMinorVersions = $stableVersions
            ->matching(CompositeConstraint::and(
                OperationConstraint::lessOrEqualTo($lastVersion),
                OperationConstraint::greaterOrEqualTo(Version::fromString($lastVersion->getMajor() . '.' . $lastVersion->getMinor() . '.0'))
            ))
            ->sortedAscending();

        return $matchingMinorVersions->first();
    }
}
