<?php

declare(strict_types=1);

namespace Roave\ApiCompareCli;

use Composer\Factory;
use Composer\Installer;
use Composer\IO\ConsoleIO;
use Roave\ApiCompare\Command;
use Roave\ApiCompare\Comparator;
use Roave\ApiCompare\DetectChanges\BCBreak\ClassBased;
use Roave\ApiCompare\DetectChanges\BCBreak\ClassConstantBased;
use Roave\ApiCompare\DetectChanges\BCBreak\FunctionBased;
use Roave\ApiCompare\DetectChanges\BCBreak\InterfaceBased;
use Roave\ApiCompare\DetectChanges\BCBreak\MethodBased;
use Roave\ApiCompare\DetectChanges\BCBreak\PropertyBased;
use Roave\ApiCompare\DetectChanges\BCBreak\TraitBased;
use Roave\ApiCompare\DetectChanges\Variance\TypeIsContravariant;
use Roave\ApiCompare\DetectChanges\Variance\TypeIsCovariant;
use Roave\ApiCompare\Factory\DirectoryReflectorFactory;
use Roave\ApiCompare\Git\GetVersionCollectionFromGitRepository;
use Roave\ApiCompare\Git\GitCheckoutRevisionToTemporaryPath;
use Roave\ApiCompare\Git\GitParseRevision;
use Roave\ApiCompare\Git\PickLastMinorVersionFromCollection;
use Roave\ApiCompare\LocateDependencies\LocateDependenciesViaComposer;
use Roave\BetterReflection\BetterReflection;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use function file_exists;

(function () : void {
    foreach ([__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../autoload.php'] as $autoload) {
        if (! file_exists($autoload)) {
            continue;
        }

        /** @noinspection PhpIncludeInspection */
        require $autoload;

        $application = new Application();
        $helperSet   = $application->getHelperSet();
        $input       = new ArgvInput();
        $output      = new ConsoleOutput();
        $astLocator  = (new BetterReflection())->astLocator();
        $composerIo  = new ConsoleIO($input, $output, $helperSet);

        $apiCompareCommand = new Command\AssertBackwardsCompatible(
            new GitCheckoutRevisionToTemporaryPath(),
            new DirectoryReflectorFactory($astLocator),
            new GitParseRevision(),
            new GetVersionCollectionFromGitRepository(),
            new PickLastMinorVersionFromCollection(),
            new LocateDependenciesViaComposer(
                function (string $installationPath) use ($composerIo) : Installer {
                    return Installer::create(
                        $composerIo,
                        (new Factory())->createComposer(
                            $composerIo,
                            null,
                            true,
                            $installationPath
                        )
                    );
                },
                $astLocator
            ),
            new Comparator(
                new ClassBased\MultipleChecksOnAClass(
                    new ClassBased\ClassBecameAbstract(),
                    new ClassBased\ClassBecameInterface(),
                    new ClassBased\ClassBecameTrait(),
                    new ClassBased\ClassBecameFinal(),
                    new ClassBased\ConstantRemoved(),
                    new ClassBased\PropertyRemoved(),
                    new ClassBased\MethodRemoved(),
                    new ClassBased\OpenClassChanged(
                        new ClassBased\MultipleChecksOnAClass(
                            new ClassBased\ConstantChanged(
                                new ClassConstantBased\MultipleChecksOnAClassConstant(
                                    new ClassConstantBased\OnlyPublicClassConstantChanged(
                                        new ClassConstantBased\MultipleChecksOnAClassConstant(
                                            new ClassConstantBased\ClassConstantVisibilityReduced(),
                                            new ClassConstantBased\ClassConstantValueChanged()
                                        )
                                    ),
                                    new ClassConstantBased\OnlyProtectedClassConstantChanged(
                                        new ClassConstantBased\MultipleChecksOnAClassConstant(
                                            new ClassConstantBased\ClassConstantVisibilityReduced(),
                                            new ClassConstantBased\ClassConstantValueChanged()
                                        )
                                    )
                                )
                            ),
                            new ClassBased\PropertyChanged(
                                new PropertyBased\MultipleChecksOnAProperty(
                                    new PropertyBased\OnlyPublicPropertyChanged(
                                        new PropertyBased\MultipleChecksOnAProperty(
                                            new PropertyBased\PropertyDocumentedTypeChanged(),
                                            new PropertyBased\PropertyDefaultValueChanged(),
                                            new PropertyBased\PropertyVisibilityReduced(),
                                            new PropertyBased\PropertyScopeChanged()
                                        )
                                    ),
                                    new PropertyBased\OnlyProtectedPropertyChanged(
                                        new PropertyBased\MultipleChecksOnAProperty(
                                            new PropertyBased\PropertyDocumentedTypeChanged(),
                                            new PropertyBased\PropertyDefaultValueChanged(),
                                            new PropertyBased\PropertyVisibilityReduced(),
                                            new PropertyBased\PropertyScopeChanged()
                                        )
                                    )
                                )
                            ),
                            new ClassBased\MethodChanged(
                                new MethodBased\MultipleChecksOnAMethod(
                                    new MethodBased\OnlyPublicMethodChanged(
                                        new MethodBased\MultipleChecksOnAMethod(
                                            new MethodBased\MethodBecameFinal(),
                                            new MethodBased\MethodConcretenessChanged(),
                                            new MethodBased\MethodScopeChanged(),
                                            new MethodBased\MethodVisibilityReduced(),
                                            new MethodBased\MethodFunctionDefinitionChanged(
                                                new FunctionBased\MultipleChecksOnAFunction(
                                                    new FunctionBased\ParameterByReferenceChanged(),
                                                    new FunctionBased\ReturnTypeByReferenceChanged(),
                                                    new FunctionBased\RequiredParameterAmountIncreased(),
                                                    new FunctionBased\ParameterDefaultValueChanged(),
                                                    new FunctionBased\ReturnTypeCovarianceChanged(new TypeIsCovariant()),
                                                    new FunctionBased\ReturnTypeChanged(),
                                                    new FunctionBased\ParameterTypeContravarianceChanged(new TypeIsContravariant()),
                                                    new FunctionBased\ParameterTypeChanged()
                                                )
                                            )
                                        )
                                    ),
                                    new MethodBased\OnlyProtectedMethodChanged(
                                        new MethodBased\MultipleChecksOnAMethod(
                                            new MethodBased\MethodBecameFinal(),
                                            new MethodBased\MethodConcretenessChanged(),
                                            new MethodBased\MethodScopeChanged(),
                                            new MethodBased\MethodVisibilityReduced(),
                                            new MethodBased\MethodFunctionDefinitionChanged(
                                                new FunctionBased\MultipleChecksOnAFunction(
                                                    new FunctionBased\ParameterByReferenceChanged(),
                                                    new FunctionBased\ReturnTypeByReferenceChanged(),
                                                    new FunctionBased\RequiredParameterAmountIncreased(),
                                                    new FunctionBased\ParameterDefaultValueChanged(),
                                                    new FunctionBased\ReturnTypeCovarianceChanged(new TypeIsCovariant()),
                                                    new FunctionBased\ReturnTypeChanged(),
                                                    new FunctionBased\ParameterTypeContravarianceChanged(new TypeIsContravariant()),
                                                    new FunctionBased\ParameterTypeChanged()
                                                )
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    ),
                    new ClassBased\FinalClassChanged(
                        new ClassBased\MultipleChecksOnAClass(
                            new ClassBased\ConstantChanged(
                                new ClassConstantBased\OnlyPublicClassConstantChanged(
                                    new ClassConstantBased\MultipleChecksOnAClassConstant(
                                        new ClassConstantBased\ClassConstantVisibilityReduced(),
                                        new ClassConstantBased\ClassConstantValueChanged()
                                    )
                                )
                            ),
                            new ClassBased\PropertyChanged(
                                new PropertyBased\OnlyPublicPropertyChanged(
                                    new PropertyBased\MultipleChecksOnAProperty(
                                        new PropertyBased\PropertyDocumentedTypeChanged(),
                                        new PropertyBased\PropertyDefaultValueChanged(),
                                        new PropertyBased\PropertyVisibilityReduced(),
                                        new PropertyBased\PropertyScopeChanged()
                                    )
                                )
                            ),
                            new ClassBased\MethodChanged(
                                new MethodBased\OnlyPublicMethodChanged(
                                    new MethodBased\MultipleChecksOnAMethod(
                                        new MethodBased\MethodBecameFinal(),
                                        new MethodBased\MethodConcretenessChanged(),
                                        new MethodBased\MethodScopeChanged(),
                                        new MethodBased\MethodVisibilityReduced(),
                                        new MethodBased\MethodFunctionDefinitionChanged(
                                            new FunctionBased\MultipleChecksOnAFunction(
                                                new FunctionBased\ParameterByReferenceChanged(),
                                                new FunctionBased\ReturnTypeByReferenceChanged(),
                                                new FunctionBased\RequiredParameterAmountIncreased(),
                                                new FunctionBased\ParameterDefaultValueChanged(),
                                                new FunctionBased\ReturnTypeCovarianceChanged(new TypeIsCovariant()),
                                                new FunctionBased\ParameterTypeContravarianceChanged(new TypeIsContravariant())
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                ),
                new InterfaceBased\MultipleChecksOnAnInterface(
                    new InterfaceBased\InterfaceBecameClass(),
                    new InterfaceBased\InterfaceBecameTrait(),
                    new InterfaceBased\MethodAdded(),
                    new InterfaceBased\UseClassBasedChecksOnAnInterface(
                        new ClassBased\MultipleChecksOnAClass(
                            new ClassBased\ConstantRemoved(),
                            new ClassBased\MethodRemoved(),
                            new ClassBased\ConstantChanged(
                                new ClassConstantBased\ClassConstantValueChanged()
                            ),
                            new ClassBased\MethodChanged(
                                new MethodBased\MultipleChecksOnAMethod(
                                    new MethodBased\MethodScopeChanged(),
                                    new MethodBased\MethodFunctionDefinitionChanged(
                                        new FunctionBased\MultipleChecksOnAFunction(
                                            new FunctionBased\ParameterByReferenceChanged(),
                                            new FunctionBased\ReturnTypeByReferenceChanged(),
                                            new FunctionBased\RequiredParameterAmountIncreased(),
                                            new FunctionBased\ParameterDefaultValueChanged(),
                                            new FunctionBased\ReturnTypeCovarianceChanged(new TypeIsCovariant()),
                                            new FunctionBased\ReturnTypeChanged(),
                                            new FunctionBased\ParameterTypeContravarianceChanged(new TypeIsContravariant()),
                                            new FunctionBased\ParameterTypeChanged()
                                        )
                                    )
                                )
                            )
                        )
                    )
                ),
                new TraitBased\MultipleChecksOnATrait(
                    new TraitBased\TraitBecameInterface(),
                    new TraitBased\TraitBecameClass(),
                    new TraitBased\UseClassBasedChecksOnATrait(
                        new ClassBased\MultipleChecksOnAClass(
                            new ClassBased\PropertyChanged(
                                new PropertyBased\MultipleChecksOnAProperty(
                                    new PropertyBased\PropertyDocumentedTypeChanged(),
                                    new PropertyBased\PropertyDefaultValueChanged(),
                                    new PropertyBased\PropertyVisibilityReduced(),
                                    new PropertyBased\PropertyScopeChanged()
                                )
                            ),
                            new ClassBased\MethodChanged(
                                new MethodBased\MultipleChecksOnAMethod(
                                    new MethodBased\MultipleChecksOnAMethod(
                                        new MethodBased\MethodBecameFinal(),
                                        new MethodBased\MethodConcretenessChanged(),
                                        new MethodBased\MethodScopeChanged(),
                                        new MethodBased\MethodVisibilityReduced(),
                                        new MethodBased\MethodFunctionDefinitionChanged(
                                            new FunctionBased\MultipleChecksOnAFunction(
                                                new FunctionBased\ParameterByReferenceChanged(),
                                                new FunctionBased\ReturnTypeByReferenceChanged(),
                                                new FunctionBased\RequiredParameterAmountIncreased(),
                                                new FunctionBased\ParameterDefaultValueChanged(),
                                                new FunctionBased\ReturnTypeCovarianceChanged(new TypeIsCovariant()),
                                                new FunctionBased\ReturnTypeChanged(),
                                                new FunctionBased\ParameterTypeContravarianceChanged(new TypeIsContravariant()),
                                                new FunctionBased\ParameterTypeChanged()
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );

        $application->add($apiCompareCommand);
        $application->setDefaultCommand($apiCompareCommand->getName());

        /** @noinspection PhpUnhandledExceptionInspection */
        $application->run($input, $output);

        return;
    }

    throw new RuntimeException('Could not find Composer autoload.php');
})();
