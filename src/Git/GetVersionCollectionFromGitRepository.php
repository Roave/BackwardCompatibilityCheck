<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use Version\Exception\InvalidVersionStringException;
use Version\Version;
use Version\VersionsCollection;
use function array_filter;
use function array_map;
use function explode;

final class GetVersionCollectionFromGitRepository implements GetVersionCollection
{
    /**
     * {@inheritDoc}
     * @throws ProcessFailedException
     * @throws LogicException
     * @throws RuntimeException
     */
    public function fromRepository(CheckedOutRepository $checkedOutRepository) : VersionsCollection
    {
        $output = (new Process(['git', 'tag', '-l']))
            ->setWorkingDirectory((string) $checkedOutRepository)
            ->mustRun()
            ->getOutput();

        return new VersionsCollection(...array_filter(
            array_map(function (string $maybeVersion) : ?Version {
                try {
                    return Version::fromString($maybeVersion);
                } catch (InvalidVersionStringException $e) {
                    return null;
                }
            }, explode("\n", $output)),
            function (?Version $version) : bool {
                return $version !== null;
            }
        ));
    }
}
