<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

final class GitParseRevision implements ParseRevision
{
    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException
     */
    public function fromStringForRepository(string $something, CheckedOutRepository $repository): Revision
    {
        return Revision::fromSha1(
            (new Process(['git', 'rev-parse', $something]))
                ->setWorkingDirectory($repository->__toString())
                ->mustRun()
                ->getOutput()
        );
    }
}
