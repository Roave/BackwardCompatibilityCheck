<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Psl\Iter;
use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\FunctionName;
use Roave\BetterReflection\Reflection\ReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;

/**
 * A function that is marked `#[\NoDiscard]` emits a warning whenever its return value is not used: downstream
 * consumers that call it without consuming its result are therefore affected.
 */
final class FunctionBecameNoDiscard implements FunctionBased
{
    private FunctionName $formatFunction;

    public function __construct()
    {
        $this->formatFunction = new FunctionName();
    }

    public function __invoke(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction,
    ): Changes {
        if ($this->isNoDiscard($toFunction) && ! $this->isNoDiscard($fromFunction)) {
            return Changes::fromList(Change::changed(
                Str\format(
                    '%s was marked "#[\NoDiscard]"',
                    ($this->formatFunction)($fromFunction),
                ),
            ));
        }

        return Changes::empty();
    }

    private function isNoDiscard(ReflectionMethod|ReflectionFunction $function): bool
    {
        return Iter\any(
            $function->getAttributes(),
            // attribute names are case-insensitive, like any other class name
            static fn (ReflectionAttribute $attribute): bool => Str\lowercase($attribute->getName()) === 'nodiscard',
        );
    }
}
