<?php
declare(strict_types=1);

namespace Roave\ApiCompare\Git;

use Assert\Assert;
use Version\Version;
use Version\VersionsCollection;

final class PickLastMinorVersionFromCollection implements PickVersionFromVersionCollection
{
    /**
     * {@inheritDoc}
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function forVersions(VersionsCollection $versions) : Version
    {
        Assert::that($versions->count())->greaterOrEqualThan(1);

        $versions->sort(VersionsCollection::SORT_DESC);

        /** @var Version $lastVersion */
        $lastVersion = $versions->getIterator()->current();
        $previousVersionInIteration = $lastVersion;
        /** @var Version $version */
        foreach ($versions as $version) {
            if ($lastVersion->getMinor() !== $version->getMinor()) {
                return $previousVersionInIteration;
            }
            $previousVersionInIteration = $version;
        }

        return $version;
    }
}
