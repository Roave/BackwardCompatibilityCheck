<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\VersionConstraint;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\VersionConstraint\StableVersionConstraint;
use Version\Version;

final class StableVersionConstraintTest extends TestCase
{
    public function testStableVersions(): void
    {
        $constraint = new StableVersionConstraint();

        self::assertTrue($constraint->assert(Version::fromString('1.0.0')));
        self::assertTrue($constraint->assert(Version::fromString('0.1.0')));
        self::assertTrue($constraint->assert(Version::fromString('0.0.1')));
        self::assertFalse($constraint->assert(Version::fromString('1.0.0-alpha.1')));
        self::assertFalse($constraint->assert(Version::fromString('1.0.0-beta.1')));
        self::assertFalse($constraint->assert(Version::fromString('1.0.0-rc.1')));
    }
}
