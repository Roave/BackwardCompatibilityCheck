<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Formatter;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Util\Exception\InvalidNodePosition;
use Roave\BetterReflection\Util\Exception\NoNodePosition;

/** @internal */
final class SymbolStartColumn
{
    /**
     * Determines the column at which a given symbol starts, if able to do so.
     *
     * Mostly exists because the reflection logic may be configured to skip parsing/collecting
     * AST node exact positions, and because some sources (especially if stubbed from PHP core)
     * could have trouble identifying the position of a symbol, given that some of them are
     * declared via custom AST added by `roave/better-reflection` post-parsing (such as `__toString()`
     * in {@see \Stringable::__toString()}).
     */
    public static function get(
        ReflectionFunction|ReflectionMethod|ReflectionProperty|ReflectionClass|ReflectionClassConstant|ReflectionConstant $symbol,
    ): int|null {
        try {
            return $symbol->getStartColumn();
        } catch (NoNodePosition | InvalidNodePosition) {
            return null;
        }
    }
}
