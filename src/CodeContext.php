<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility;

use Roave\BetterReflection\Reflector\Reflector;

/**
 * Holds the reflectors of both codebases being compared, for checks that need to look up
 * symbols across them (e.g. resolving a type of the old codebase within the new codebase).
 */
final readonly class CodeContext
{
    public function __construct(
        public Reflector $beforeCodebase,
        public Reflector $afterCodebase,
    ) {
    }
}
