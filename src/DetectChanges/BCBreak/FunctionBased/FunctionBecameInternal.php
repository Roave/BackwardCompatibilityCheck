<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use function Safe\preg_match;
use function Safe\sprintf;

/**
 * A function that is marked internal is no available to downstream consumers.
 */
final class FunctionBecameInternal implements FunctionBased
{
    /** @var ReflectionFunctionAbstractName */
    private $formatFunction;

    public function __construct()
    {
        $this->formatFunction = new ReflectionFunctionAbstractName();
    }

    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes
    {
        if ($this->isInternalDocComment($toFunction->getDocComment())
            && ! $this->isInternalDocComment($fromFunction->getDocComment())
        ) {
            return Changes::fromList(Change::changed(
                sprintf(
                    '%s was marked "@internal"',
                    $this->formatFunction->__invoke($fromFunction),
                ),
                true
            ));
        }

        return Changes::empty();
    }

    private function isInternalDocComment(string $comment) : bool
    {
        return preg_match('/\s+@internal\s+/', $comment) === 1;
    }
}
