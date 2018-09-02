<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility;

use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\InterfaceBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased\TraitBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use function array_filter;
use function array_map;
use function sprintf;

final class CompareClasses implements CompareApi
{
    /** @var ClassBased */
    private $classBasedComparisons;

    /** @var InterfaceBased */
    private $interfaceBasedComparisons;

    /** @var TraitBased */
    private $traitBasedComparisons;

    public function __construct(
        ClassBased $classBasedComparisons,
        InterfaceBased $interfaceBasedComparisons,
        TraitBased $traitBasedComparisons
    ) {
        $this->classBasedComparisons     = $classBasedComparisons;
        $this->interfaceBasedComparisons = $interfaceBasedComparisons;
        $this->traitBasedComparisons     = $traitBasedComparisons;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(
        ClassReflector $definedSymbols,
        ClassReflector $pastSourcesWithDependencies,
        ClassReflector $newSourcesWithDependencies
    ) : Changes {
        $definedApiClassNames = array_map(
            function (ReflectionClass $class) : string {
                return $class->getName();
            },
            array_filter(
                $definedSymbols->getAllClasses(),
                function (ReflectionClass $class) : bool {
                    return ! $class->isAnonymous();
                }
            )
        );

        return Changes::fromIterator((function () use ($definedApiClassNames, $pastSourcesWithDependencies, $newSourcesWithDependencies) {
            foreach ($definedApiClassNames as $apiClassName) {
                /** @var ReflectionClass $oldSymbol */
                $oldSymbol = $pastSourcesWithDependencies->reflect($apiClassName);
                yield from $this->examineSymbol($oldSymbol, $newSourcesWithDependencies);
            }
        })());
    }

    private function examineSymbol(
        ReflectionClass $oldSymbol,
        ClassReflector $newSourcesWithDependencies
    ) : \Generator {
        try {
            /** @var ReflectionClass $newClass */
            $newClass = $newSourcesWithDependencies->reflect($oldSymbol->getName());
        } catch (IdentifierNotFound $exception) {
            yield Change::removed(sprintf('Class %s has been deleted', $oldSymbol->getName()), true);

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
}
