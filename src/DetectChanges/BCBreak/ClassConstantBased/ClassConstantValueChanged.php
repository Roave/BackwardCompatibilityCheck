<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;

use Psl\Str;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;

use function dirname;
use function is_string;
use function realpath;
use function str_replace;
use function var_export;

final class ClassConstantValueChanged implements ClassConstantBased
{
    private const MAGIC_DIR_OR_FILE_VALUE = '__DIR_OR_FILE__';

    public function __invoke(ReflectionClassConstant $fromConstant, ReflectionClassConstant $toConstant): Changes
    {
        if ($fromConstant->isPrivate()) {
            return Changes::empty();
        }

        /** @psalm-suppress MixedAssignment */
        $fromValue = $this->getValueWithDirectoryRemoved($fromConstant);
        /** @psalm-suppress MixedAssignment */
        $toValue = $this->getValueWithDirectoryRemoved($toConstant);

        if ($fromValue === $toValue) {
            return Changes::empty();
        }

        return Changes::fromList(Change::changed(
            Str\format(
                'Value of constant %s::%s changed from %s to %s',
                $fromConstant->getDeclaringClass()->getName(),
                $fromConstant->getName(),
                var_export($fromValue, true),
                var_export($toValue, true)
            ),
            true
        ));
    }

    private function getValueWithDirectoryRemoved(ReflectionClassConstant $constant): mixed
    {
        $value = $constant->getValue();

        if (! is_string($value)) {
            return $value;
        }

        $filePath = $constant->getDeclaringClass()->getFileName();
        if (! $filePath) {
            return $value;
        }

        return str_replace(dirname(realpath($filePath)), self::MAGIC_DIR_OR_FILE_VALUE, $value);
    }
}
