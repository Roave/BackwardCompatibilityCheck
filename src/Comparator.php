<?php

declare(strict_types=1);

namespace Roave\ApiCompare;

use Roave\ApiCompare\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\ApiCompare\DetectChanges\BCBreak\InterfaceBased\InterfaceBased;
use Roave\ApiCompare\DetectChanges\BCBreak\TraitBased\TraitBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use function array_map;
use function sprintf;

class Comparator
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
     * @param ClassReflector $definedSymbols              containing only defined symbols in the compared API
     * @param ClassReflector $pastSourcesWithDependencies capable of giving us symbols with their dependencies from the
     *                                                    old version of the sources
     * @param ClassReflector $newSourcesWithDependencies  capable of giving us symbols with their dependencies from the
     *                                                    old version of the sources
     */
    public function compare(
        ClassReflector $definedSymbols,
        ClassReflector $pastSourcesWithDependencies,
        ClassReflector $newSourcesWithDependencies
    ) : Changes {
        $changelog = Changes::empty();

        $definedApiClassNames = array_map(function (ReflectionClass $class) : string {
            return $class->getName();
        }, $definedSymbols->getAllClasses());

        foreach ($definedApiClassNames as $apiClassName) {
            /** @var ReflectionClass $oldSymbol */
            $oldSymbol = $pastSourcesWithDependencies->reflect($apiClassName);
            $changelog = $this->examineSymbol($changelog, $oldSymbol, $newSourcesWithDependencies);
        }

        return $changelog;
    }

    private function examineSymbol(
        Changes $changelog,
        ReflectionClass $oldSymbol,
        ClassReflector $newSourcesWithDependencies
    ) : Changes {
        try {
            /** @var ReflectionClass $newClass */
            $newClass = $newSourcesWithDependencies->reflect($oldSymbol->getName());
        } catch (IdentifierNotFound $exception) {
            return $changelog->withAddedChange(
                Change::removed(sprintf('Class %s has been deleted', $oldSymbol->getName()), true)
            );
        }

        if ($oldSymbol->isInterface()) {
            return $changelog->mergeWith($this->interfaceBasedComparisons->__invoke($oldSymbol, $newClass));
        }

        if ($oldSymbol->isTrait()) {
            return $changelog->mergeWith($this->traitBasedComparisons->__invoke($oldSymbol, $newClass));
        }

        return $changelog->mergeWith($this->classBasedComparisons->__invoke($oldSymbol, $newClass));
    }
}
