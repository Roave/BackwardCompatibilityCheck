<?php

declare(strict_types=1);

namespace Roave\ApiCompareCli;

use Composer\Factory;
use Composer\Installer;
use Composer\IO\ConsoleIO;
use Roave\BackwardCompatibility\Command;
use Roave\BackwardCompatibility\CompareClasses;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsContravariant;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsCovariant;
use Roave\BackwardCompatibility\Factory\DirectoryReflectorFactory;
use Roave\BackwardCompatibility\Git\GetVersionCollectionFromGitRepository;
use Roave\BackwardCompatibility\Git\GitCheckoutRevisionToTemporaryPath;
use Roave\BackwardCompatibility\Git\GitParseRevision;
use Roave\BackwardCompatibility\Git\PickLastMinorVersionFromCollection;
use Roave\BackwardCompatibility\LocateDependencies\LocateDependenciesViaComposer;
use Roave\BetterReflection\BetterReflection;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use function file_exists;

(function () : void {
    (function () : void {
        $autoloaderLocations = [
            __DIR__ . '/../vendor/autoload.php', // Installed by cloning the project and running `composer install`
            __DIR__ . '/../../../autoload.php',  // Installed via `composer require`
        ];

        foreach ($autoloaderLocations as $autoload) {
            if (file_exists($autoload)) {
                /** @noinspection PhpIncludeInspection */
                require_once $autoload;

                return;
            }
        }

        throw new RuntimeException('Could not find Composer autoload.php');
    })();

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
        new CompareClasses(
            new ClassBased\SkipClassBasedErrors(
                new ClassBased\ExcludeAnonymousClasses(
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
                                    new ClassConstantBased\SkipClassConstantBasedErrors(
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
                                    )
                                ),
                                new ClassBased\PropertyChanged(
                                    new PropertyBased\SkipPropertyBasedErrors(
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
                                    )
                                ),
                                new ClassBased\MethodChanged(
                                    new MethodBased\SkipMethodBasedErrors(
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
                            )
                        ),
                        new ClassBased\FinalClassChanged(
                            new ClassBased\MultipleChecksOnAClass(
                                new ClassBased\ConstantChanged(
                                    new ClassConstantBased\SkipClassConstantBasedErrors(
                                        new ClassConstantBased\OnlyPublicClassConstantChanged(
                                            new ClassConstantBased\MultipleChecksOnAClassConstant(
                                                new ClassConstantBased\ClassConstantVisibilityReduced(),
                                                new ClassConstantBased\ClassConstantValueChanged()
                                            )
                                        )
                                    )
                                ),
                                new ClassBased\PropertyChanged(
                                    new PropertyBased\SkipPropertyBasedErrors(
                                        new PropertyBased\OnlyPublicPropertyChanged(
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
                                    new MethodBased\SkipMethodBasedErrors(
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
                        )
                    )
                )
            ),
            new InterfaceBased\SkipInterfaceBasedErrors(
                new InterfaceBased\MultipleChecksOnAnInterface(
                    new InterfaceBased\InterfaceBecameClass(),
                    new InterfaceBased\InterfaceBecameTrait(),
                    new InterfaceBased\MethodAdded(),
                    new InterfaceBased\UseClassBasedChecksOnAnInterface(
                        new ClassBased\MultipleChecksOnAClass(
                            new ClassBased\ConstantRemoved(),
                            new ClassBased\MethodRemoved(),
                            new ClassBased\ConstantChanged(
                                new ClassConstantBased\SkipClassConstantBasedErrors(
                                    new ClassConstantBased\ClassConstantValueChanged()
                                )
                            ),
                            new ClassBased\MethodChanged(
                                new MethodBased\SkipMethodBasedErrors(
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
                    )
                )
            ),
            new TraitBased\SkipTraitBasedErrors(
                new TraitBased\MultipleChecksOnATrait(
                    new TraitBased\TraitBecameInterface(),
                    new TraitBased\TraitBecameClass(),
                    new TraitBased\UseClassBasedChecksOnATrait(
                        new ClassBased\MultipleChecksOnAClass(
                            new ClassBased\PropertyChanged(
                                new PropertyBased\SkipPropertyBasedErrors(
                                    new PropertyBased\MultipleChecksOnAProperty(
                                        new PropertyBased\PropertyDocumentedTypeChanged(),
                                        new PropertyBased\PropertyDefaultValueChanged(),
                                        new PropertyBased\PropertyVisibilityReduced(),
                                        new PropertyBased\PropertyScopeChanged()
                                    )
                                )
                            ),
                            new ClassBased\MethodChanged(
                                new MethodBased\SkipMethodBasedErrors(
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
            )
        )
    );

    $application->add($apiCompareCommand);
    $application->setDefaultCommand($apiCompareCommand->getName());

    $application->run($input, $output);
})();
