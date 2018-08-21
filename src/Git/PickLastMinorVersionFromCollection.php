<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Assert\Assert;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;
use Version\Constraint\ComparisonConstraint;
use Version\Constraint\CompositeConstraint;
use Version\Version;
use Version\VersionsCollection;
use function array_values;
use function iterator_to_array;

final class PickLastMinorVersionFromCollection implements PickVersionFromVersionCollection
{
    /**
     * {@inheritDoc}
     * @throws LogicException
     * @throws RuntimeException
     */
    public function forVersions(VersionsCollection $versions) : Version
    {
        Assert::that($versions->count())
            ->greaterThan(0, 'Cannot determine latest minor version from an empty collection');

        $versionsSortedDescending = $versions->sortedDescending();

        /** @var Version $lastVersion */
        $lastVersion = array_values(iterator_to_array($versionsSortedDescending))[0];

        $matchingMinorVersions = $versions
            ->matching(new CompositeConstraint(
                CompositeConstraint::OPERATOR_AND,
                new ComparisonConstraint(ComparisonConstraint::OPERATOR_LTE, $lastVersion),
                new ComparisonConstraint(
                    ComparisonConstraint::OPERATOR_GTE,
                    Version::fromString($lastVersion->getMajor() . '.' . $lastVersion->getMinor() . '.0')
                )
            ))
            ->sortedAscending();

        /** @var Version[] $matchingMinorVersionsAsArray */
        $matchingMinorVersionsAsArray = array_values(iterator_to_array($matchingMinorVersions));

        return $matchingMinorVersionsAsArray[0];
    }
}
