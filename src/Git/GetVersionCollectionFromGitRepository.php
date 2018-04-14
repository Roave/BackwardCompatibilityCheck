<?php
declare(strict_types=1);

namespace Roave\ApiCompare\Git;

use Symfony\Component\Process\Process;
use Version\VersionsCollection;

final class GetVersionCollectionFromGitRepository implements GetVersionCollection
{
    /**
     * {@inheritDoc}
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function fromRepository(CheckedOutRepository $checkedOutRepository) : VersionsCollection
    {
        $output = (new Process(['git', 'tag', '-l']))
            ->setWorkingDirectory((string)$checkedOutRepository)
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
