<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\ReflectionFunctionAbstractName;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use function array_combine;
use function array_filter;
use function array_intersect_key;
use function array_map;
use function array_values;
use function assert;
use function sprintf;
use function var_export;

/**
 * A default value for a parameter should not change, as that can lead to change in expected execution
 * behavior.
 */
final class ParameterDefaultValueChanged implements FunctionBased
{
    /** @var ReflectionFunctionAbstractName */
    private $formatFunction;

    public function __construct()
    {
        $this->formatFunction = new ReflectionFunctionAbstractName();
    }

    public function __invoke(ReflectionFunctionAbstract $fromFunction, ReflectionFunctionAbstract $toFunction) : Changes
    {
        $fromParametersWithDefaults = $this->defaultParameterValues($fromFunction);
        $toParametersWithDefaults   = $this->defaultParameterValues($toFunction);

        $changes = Changes::empty();

        foreach (array_intersect_key($fromParametersWithDefaults, $toParametersWithDefaults) as $parameterIndex => $parameter) {
            assert($parameter instanceof ReflectionParameter);

            $defaultValueFrom = $parameter->getDefaultValue();
            $defaultValueTo   = $toParametersWithDefaults[$parameterIndex]->getDefaultValue();

            if ($defaultValueFrom === $defaultValueTo) {
                continue;
            }

            $changes = $changes->mergeWith(Changes::fromList(Change::changed(
                sprintf(
                    'Default parameter value for for parameter $%s of %s changed from %s to %s',
                    $parameter->getName(),
                    $this->formatFunction->__invoke($fromFunction),
                    var_export($defaultValueFrom, true),
                    var_export($defaultValueTo, true)
                ),
                true
            )));
        }

        return $changes;
    }

    /** @return ReflectionParameter[] indexed by parameter index */
    private function defaultParameterValues(ReflectionFunctionAbstract $function) : array
    {
        $optionalParameters = array_values(array_filter(
            $function->getParameters(),
            static function (ReflectionParameter $parameter) : bool {
                return $parameter->isDefaultValueAvailable();
            }
        ));

        return array_combine(
            array_map(static function (ReflectionParameter $parameter) : int {
                return $parameter->getPosition();
            }, $optionalParameters),
            $optionalParameters
        );
    }
}
