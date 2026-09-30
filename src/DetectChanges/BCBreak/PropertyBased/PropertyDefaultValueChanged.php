<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use PhpParser\Node\Expr;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionPropertyName;
use Roave\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use Roave\BetterReflection\Reflection\ReflectionProperty;

use function var_export;

final class PropertyDefaultValueChanged implements PropertyBased
{
    private ReflectionPropertyName $formatProperty;
    private PrettyPrinterAbstract $prettyPrinter;

    public function __construct()
    {
        $this->formatProperty = new ReflectionPropertyName();
        $this->prettyPrinter  = new Standard();
    }

    public function __invoke(ReflectionProperty $fromProperty, ReflectionProperty $toProperty): Changes
    {
        try {
            $fromPropertyDefaultValue = $fromProperty->getDefaultValue();
            $toPropertyDefaultValue   = $toProperty->getDefaultValue();
        } catch (UnableToCompileNode $unableToCompileNode) {
            $fromPropertyDefaultExpression = $fromProperty->getDefaultValueExpression();
            $toPropertyDefaultExpression   = $toProperty->getDefaultValueExpression();

            if (
                $toPropertyDefaultExpression instanceof Expr &&
                $fromPropertyDefaultExpression instanceof Expr &&
                $this->prettyPrinter->prettyPrintExpr($toPropertyDefaultExpression) === $this->prettyPrinter->prettyPrintExpr($fromPropertyDefaultExpression)
            ) {
                return Changes::empty();
            }

            throw $unableToCompileNode;
        }

        if ($fromPropertyDefaultValue === $toPropertyDefaultValue) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'Property %s changed default value from %s to %s',
                ($this->formatProperty)($fromProperty),
                var_export($fromPropertyDefaultValue, true),
                var_export($toPropertyDefaultValue, true),
            ),
        ));
    }
}
