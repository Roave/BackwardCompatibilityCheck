<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClass;
use function Safe\preg_match;

/**
 * Interfaces marked "internal" (docblock) are not affected by BC checks.
 */
final class ExcludeInternalInterface implements InterfaceBased
{
    /** @var InterfaceBased */
    private $check;

    public function __construct(InterfaceBased $check)
    {
        $this->check = $check;
    }

    public function __invoke(ReflectionClass $fromInterface, ReflectionClass $toInterface) : Changes
    {
        if ($this->isInternalDocComment($fromInterface->getDocComment())) {
            return Changes::empty();
        }

        return $this->check->__invoke($fromInterface, $toInterface);
    }

    private function isInternalDocComment(string $comment) : bool
    {
        return preg_match('/\s+@internal\s+/', $comment) === 1;
    }
}
