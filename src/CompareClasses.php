<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility;

use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ClassBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\InterfaceBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased\TraitBased;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
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
            return $changelog->mergeWith(Changes::fromList(
                Change::removed(sprintf('Class %s has been deleted', $oldSymbol->getName()), true)
            ));
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
