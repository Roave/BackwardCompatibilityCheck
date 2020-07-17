<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use Version\Exception\InvalidVersionString;
use Version\Version;
use Version\VersionCollection;

use function array_filter;
use function array_map;
use function explode;

final class GetVersionCollectionFromGitRepository implements GetVersionCollection
{
    /**
     * {@inheritDoc}
     *
     * @throws ProcessFailedException
     * @throws LogicException
     * @throws RuntimeException
     */
    public function fromRepository(CheckedOutRepository $checkedOutRepository): VersionCollection
    {
        $output = (new Process(['git', 'tag', '-l'], $checkedOutRepository->__toString()))
            ->mustRun()
            ->getOutput();

        return new VersionCollection(...array_filter(
            array_map(static function (string $maybeVersion): ?Version {
                try {
                    return Version::fromString($maybeVersion);
                } catch (InvalidVersionString $e) {
                    return null;
                }
            }, explode("\n", $output))
        ));
    }
}
