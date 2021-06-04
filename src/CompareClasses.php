<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility;

use Generator;
use Psl\Dict;
use Psl\Regex;
use Psl\Str;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\InterfaceBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased\TraitBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;

final class CompareClasses implements CompareApi
{
    private ClassBased $classBasedComparisons;

    private InterfaceBased $interfaceBasedComparisons;

    private TraitBased $traitBasedComparisons;

    public function __construct(
        ClassBased $classBasedComparisons,
        InterfaceBased $interfaceBasedComparisons,
        TraitBased $traitBasedComparisons
    ) {
        $this->classBasedComparisons     = $classBasedComparisons;
        $this->interfaceBasedComparisons = $interfaceBasedComparisons;
        $this->traitBasedComparisons     = $traitBasedComparisons;
    }

    public function __invoke(
        ClassReflector $definedSymbols,
        ClassReflector $pastSourcesWithDependencies,
        ClassReflector $newSourcesWithDependencies
    ): Changes {
        $definedApiClassNames = Dict\map(
            Dict\filter(
                $definedSymbols->getAllClasses(),
                function (ReflectionClass $class): bool {
                    return ! ($class->isAnonymous() || $this->isInternalDocComment($class->getDocComment()));
                }
            ),
            static function (ReflectionClass $class): string {
                return $class->getName();
            }
        );

        return Changes::fromIterator($this->makeSymbolsIterator(
            $definedApiClassNames,
            $pastSourcesWithDependencies,
            $newSourcesWithDependencies
        ));
    }

    /**
     * @param string[] $definedApiClassNames
     *
     * @return iterable|Change[]
     */
    private function makeSymbolsIterator(
        array $definedApiClassNames,
        ClassReflector $pastSourcesWithDependencies,
        ClassReflector $newSourcesWithDependencies
    ): iterable {
        foreach ($definedApiClassNames as $apiClassName) {
            $oldSymbol = $pastSourcesWithDependencies->reflect($apiClassName);

            yield from $this->examineSymbol($oldSymbol, $newSourcesWithDependencies);
        }
    }

    private function examineSymbol(
        ReflectionClass $oldSymbol,
        ClassReflector $newSourcesWithDependencies
    ): Generator {
        try {
            $newClass = $newSourcesWithDependencies->reflect($oldSymbol->getName());
        } catch (IdentifierNotFound $exception) {
            yield Change::removed(Str\format('Class %s has been deleted', $oldSymbol->getName()), true);

            return;
        }

        if ($oldSymbol->isInterface()) {
            yield from $this->interfaceBasedComparisons->__invoke($oldSymbol, $newClass);

            return;
        }

        if ($oldSymbol->isTrait()) {
            yield from $this->traitBasedComparisons->__invoke($oldSymbol, $newClass);

            return;
        }

        yield from $this->classBasedComparisons->__invoke($oldSymbol, $newClass);
    }

    private function isInternalDocComment(string $comment): bool
    {
        return Regex\matches($comment, '/\s+@internal\s+/');
    }
}
