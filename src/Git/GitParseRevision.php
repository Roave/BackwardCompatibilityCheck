<?php
declare(strict_types=1);

namespace Roave\ApiCompare\Git;

use Symfony\Component\Process\Process;

final class GitParseRevision implements ParseRevision
{
    /**
     * {@inheritDoc}
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function fromStringForRepository(string $something, CheckedOutRepository $repository) : Revision
    {
        return Revision::fromSha1(
            (new Process(['git', 'rev-parse', $something]))
                ->setWorkingDirectory((string)$repository)
                ->mustRun()
                ->getOutput()
        );
    }
}
