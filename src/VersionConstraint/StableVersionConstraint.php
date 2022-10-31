<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\VersionConstraint;

use Version\Comparison\Constraint\Constraint;
use Version\Version;

final class StableVersionConstraint implements Constraint
{
    public function assert(Version $version): bool
    {
        return ! $version->isPreRelease();
    }
}
