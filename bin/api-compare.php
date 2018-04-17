<?php

declare(strict_types=1);

namespace Roave\ApiCompareCli;

use Roave\ApiCompare\Command;
use Roave\ApiCompare\Comparator;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassConstantBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\FunctionBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\InterfaceBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\MethodBased;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased;
use Roave\ApiCompare\Comparator\Variance\TypeIsContravariant;
use Roave\ApiCompare\Comparator\Variance\TypeIsCovariant;
use Roave\ApiCompare\Factory\DirectoryReflectorFactory;
use Roave\ApiCompare\Git\GetVersionCollectionFromGitRepository;
use Roave\ApiCompare\Git\GitCheckoutRevisionToTemporaryPath;
use Roave\ApiCompare\Git\GitParseRevision;
use Roave\ApiCompare\Git\PickLastMinorVersionFromCollection;
use RuntimeException;
use Symfony\Component\Console\Application;
use function file_exists;

(function () : void {
    foreach ([__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../autoload.php'] as $autoload) {
        if (! file_exists($autoload)) {
            continue;
        }

        /** @noinspection PhpIncludeInspection */
        require $autoload;

        $apiCompareCommand = new Command\ApiCompare(
            new GitCheckoutRevisionToTemporaryPath(),
            new DirectoryReflectorFactory(),
            new GitParseRevision(),
            new GetVersionCollectionFromGitRepository(),
            new PickLastMinorVersionFromCollection(),
            new Comparator(
                new ClassBased\MultiClassBased(
                    new ClassBased\ClassBecameAbstract(),
                    new ClassBased\ClassBecameFinal(),
                    new ClassBased\ConstantRemoved(),
                    new ClassBased\PropertyRemoved(),
                    new ClassBased\MethodRemoved()
                ),
                new InterfaceBased\MethodAdded(),
                new MethodBased\MultiMethodBased(
                    new MethodBased\MethodConcretenessChanged(),
                    new MethodBased\MethodScopeChanged(),
                    new MethodBased\MethodVisibilityReduced(),
                    new MethodBased\AccessibleMethodFunctionBasedChange(
                        new FunctionBased\MultiFunctionBased(
                            new FunctionBased\ParameterByReferenceChanged(),
                            new FunctionBased\ReturnTypeByReferenceChanged(),
                            new FunctionBased\RequiredParameterAmountIncreased(),
                            new FunctionBased\ParameterDefaultValueChanged(),
                            new FunctionBased\ReturnTypeCovarianceChanged(new TypeIsCovariant()),
                            new FunctionBased\ParameterTypeContravarianceChanged(new TypeIsContravariant())
                        )
                    )
                ),
                new PropertyBased\MultiPropertyBased(
                    new PropertyBased\PropertyDocumentedTypeChanged(),
                    new PropertyBased\PropertyDefaultValueChanged(),
                    new PropertyBased\PropertyVisibilityReduced(),
                    new PropertyBased\PropertyScopeChanged()
                ),
                new ClassConstantBased\MultiConstantBased(
                    new ClassConstantBased\ConstantVisibilityReduced(),
                    new ClassConstantBased\ConstantValueChanged()
                )
            )
        );

        $application = new Application();
        $application->add($apiCompareCommand);
        $application->setDefaultCommand($apiCompareCommand->getName());

        /** @noinspection PhpUnhandledExceptionInspection */
        $application->run();

        return;
    }

    throw new RuntimeException('Could not find Composer autoload.php');
})();
