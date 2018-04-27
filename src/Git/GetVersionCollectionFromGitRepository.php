<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use Version\VersionsCollection;
use function array_filter;
use function explode;
use function trim;

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

        // @todo handle invalid versions more gracefully (drop them)
        return VersionsCollection::fromArray(array_filter(
            explode("\n", $output),
            function (string $maybeVersion) {
                return trim($maybeVersion) !== '';
            }
        ));
    }
}
