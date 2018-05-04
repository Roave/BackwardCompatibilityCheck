<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;
use Version\Version;
use Version\VersionsCollection;

final class PickLastMinorVersionFromCollection implements PickVersionFromVersionCollection
{
    /**
     * {@inheritDoc}
     * @throws LogicException
     * @throws RuntimeException
     */
    public function forVersions(VersionsCollection $versions) : Version
    {
        $versions->sort(VersionsCollection::SORT_DESC);

        /** @var Version $lastVersion */
        $lastVersion                = $versions->getIterator()->current();
        $previousVersionInIteration = $lastVersion;
        /** @var Version $version */
        foreach ($versions as $version) {
            if ($lastVersion->getMinor() !== $version->getMinor()) {
                return $previousVersionInIteration;
            }
            $previousVersionInIteration = $version;
        }

        return $previousVersionInIteration;
    }
}
