<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Psl\Shell;

final class GitParseRevision implements ParseRevision
{
    /**
     * {@inheritDoc}
     *
     * @throws Shell\Exception\RuntimeException
     */
    public function fromStringForRepository(string $something, CheckedOutRepository $repository): Revision
    {
        return Revision::fromSha1(
            Shell\execute('git', ['rev-parse', $something], $repository->__toString()),
        );
    }
}
