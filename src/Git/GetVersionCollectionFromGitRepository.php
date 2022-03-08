<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Psl\Shell;
use Psl\Str;
use Psl\Type;
use Psl\Vec;
use Version\Exception\InvalidVersionString;
use Version\Version;
use Version\VersionCollection;

final class GetVersionCollectionFromGitRepository implements GetVersionCollection
{
    /**
     * @throws Shell\Exception\RuntimeException
     * @throws Shell\Exception\FailedExecutionException
     */
    public function fromRepository(CheckedOutRepository $repository): VersionCollection
    {
        $output = Shell\execute('git', ['tag', '-l'], $repository->__toString());

        return new VersionCollection(...Vec\filter_nulls(Vec\map(Str\split($output, "\n"), static function (string $maybeVersion): ?Version {
            try {
                return Type\object(Version::class)
                    ->coerce(Version::fromString($maybeVersion));
            } catch (InvalidVersionString) {
                return null;
            }
        })));
    }
}
