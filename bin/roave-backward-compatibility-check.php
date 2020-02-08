<?php

declare(strict_types=1);

namespace Roave\ApiCompareCli;

use Composer\Factory;
use Composer\Installer;
use Composer\IO\ConsoleIO;
use PackageVersions\Versions;
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
use Roave\BackwardCompatibility\Factory\ComposerInstallationReflectorFactory;
use Roave\BackwardCompatibility\Git\GetVersionCollectionFromGitRepository;
use Roave\BackwardCompatibility\Git\GitCheckoutRevisionToTemporaryPath;
use Roave\BackwardCompatibility\Git\GitParseRevision;
use Roave\BackwardCompatibility\Git\PickLastMinorVersionFromCollection;
use Roave\BackwardCompatibility\LocateDependencies\LocateDependenciesViaComposer;
use Roave\BackwardCompatibility\LocateSources\LocateSourcesViaComposerJson;
use Roave\BetterReflection\BetterReflection;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use function file_exists;

(static function () : void {
    (static function () : void {
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

    $application = new Application('roave/backward-compatibility-check', Versions::getVersion('roave/backward-compatibility-check'));
    $helperSet   = $application->getHelperSet();
    $input       = new ArgvInput();
    $output      = new ConsoleOutput();
    $astLocator  = (new BetterReflection())->astLocator();
    $composerIo  = new ConsoleIO($input, $output, $helperSet);

    $apiCompareCommand = new Command\AssertBackwardsCompatible(
        new GitCheckoutRevisionToTemporaryPath(),
        new ComposerInstallationReflectorFactory(new LocateSourcesViaComposerJson($astLocator)),
        new GitParseRevision(),
        new GetVersionCollectionFromGitRepository(),
        new PickLastMinorVersionFromCollection(),
        new LocateDependenciesViaComposer(
            static function (string $installationPath) use ($composerIo) : Installer {
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
            new ClassBased\SkipClassBasedErrors(new ClassBased\ExcludeAnonymousClasses(new ClassBased\ExcludeInternalClass(
                new ClassBased\MultipleChecksOnAClass(
                    new ClassBased\SkipClassBasedErrors(new ClassBased\ClassBecameAbstract()),
                    new ClassBased\SkipClassBasedErrors(new ClassBased\ClassBecameInterface()),
                    new ClassBased\SkipClassBasedErrors(new ClassBased\ClassBecameTrait()),
                    new ClassBased\SkipClassBasedErrors(new ClassBased\ClassBecameFinal()),
                    new ClassBased\SkipClassBasedErrors(new ClassBased\ConstantRemoved()),
                    new ClassBased\SkipClassBasedErrors(new ClassBased\PropertyRemoved()),
                    new ClassBased\SkipClassBasedErrors(new ClassBased\MethodRemoved()),
                    new ClassBased\SkipClassBasedErrors(new ClassBased\AncestorRemoved()),
                    new ClassBased\SkipClassBasedErrors(new ClassBased\ClassBecameInternal()),
                    new ClassBased\SkipClassBasedErrors(new ClassBased\OpenClassChanged(
                        new ClassBased\MultipleChecksOnAClass(
                            new ClassBased\SkipClassBasedErrors(new ClassBased\ConstantChanged(
                                new ClassConstantBased\MultipleChecksOnAClassConstant(
                                    new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\OnlyPublicClassConstantChanged(
                                        new ClassConstantBased\MultipleChecksOnAClassConstant(
                                            new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\ClassConstantVisibilityReduced()),
                                            new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\ClassConstantValueChanged())
                                        )
                                    )),
                                    new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\OnlyProtectedClassConstantChanged(
                                        new ClassConstantBased\MultipleChecksOnAClassConstant(
                                            new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\ClassConstantVisibilityReduced()),
                                            new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\ClassConstantValueChanged())
                                        )
                                    ))
                                )
                            )),
                            new ClassBased\SkipClassBasedErrors(new ClassBased\PropertyChanged(
                                new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\ExcludeInternalProperty(new PropertyBased\MultipleChecksOnAProperty(
                                    new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\OnlyPublicPropertyChanged(
                                        new PropertyBased\MultipleChecksOnAProperty(
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyBecameInternal()),
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyDocumentedTypeChanged()),
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyDefaultValueChanged()),
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyVisibilityReduced()),
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyScopeChanged())
                                        )
                                    )),
                                    new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\OnlyProtectedPropertyChanged(
                                        new PropertyBased\MultipleChecksOnAProperty(
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyBecameInternal()),
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyDocumentedTypeChanged()),
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyDefaultValueChanged()),
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyVisibilityReduced()),
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyScopeChanged())
                                        )
                                    ))
                                )))
                            )),
                            new ClassBased\SkipClassBasedErrors(new ClassBased\MethodChanged(
                                new MethodBased\SkipMethodBasedErrors(new MethodBased\ExcludeInternalMethod(new MethodBased\MultipleChecksOnAMethod(
                                    new MethodBased\SkipMethodBasedErrors(new MethodBased\OnlyPublicMethodChanged(
                                        new MethodBased\MultipleChecksOnAMethod(
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodBecameFinal()),
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodConcretenessChanged()),
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodScopeChanged()),
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodVisibilityReduced()),
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodFunctionDefinitionChanged(
                                                new FunctionBased\MultipleChecksOnAFunction(
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\FunctionBecameInternal()),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterByReferenceChanged()),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ReturnTypeByReferenceChanged()),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\RequiredParameterAmountIncreased()),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterDefaultValueChanged()),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ReturnTypeCovarianceChanged(new TypeIsCovariant())),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ReturnTypeChanged()),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterTypeContravarianceChanged(new TypeIsContravariant())),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterTypeChanged())
                                                )
                                            ))
                                        )
                                    )),
                                    new MethodBased\SkipMethodBasedErrors(new MethodBased\OnlyProtectedMethodChanged(
                                        new MethodBased\MultipleChecksOnAMethod(
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodBecameFinal()),
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodConcretenessChanged()),
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodScopeChanged()),
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodVisibilityReduced()),
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodFunctionDefinitionChanged(
                                                new FunctionBased\MultipleChecksOnAFunction(
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\FunctionBecameInternal()),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterByReferenceChanged()),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ReturnTypeByReferenceChanged()),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\RequiredParameterAmountIncreased()),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterDefaultValueChanged()),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ReturnTypeCovarianceChanged(new TypeIsCovariant())),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ReturnTypeChanged()),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterTypeContravarianceChanged(new TypeIsContravariant())),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterTypeChanged())
                                                )
                                            ))
                                        )
                                    ))
                                )))
                            ))
                        )
                    )),
                    new ClassBased\SkipClassBasedErrors(new ClassBased\FinalClassChanged(
                        new ClassBased\MultipleChecksOnAClass(
                            new ClassBased\SkipClassBasedErrors(new ClassBased\ConstantChanged(
                                new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\OnlyPublicClassConstantChanged(
                                    new ClassConstantBased\MultipleChecksOnAClassConstant(
                                        new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\ClassConstantVisibilityReduced()),
                                        new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\ClassConstantValueChanged())
                                    )
                                ))
                            )),
                            new ClassBased\SkipClassBasedErrors(new ClassBased\PropertyChanged(
                                new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\OnlyPublicPropertyChanged(
                                    new PropertyBased\MultipleChecksOnAProperty(
                                        new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyBecameInternal()),
                                        new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyDocumentedTypeChanged()),
                                        new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyDefaultValueChanged()),
                                        new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyVisibilityReduced()),
                                        new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyScopeChanged())
                                    )
                                ))
                            )),
                            new ClassBased\SkipClassBasedErrors(new ClassBased\MethodChanged(
                                new MethodBased\SkipMethodBasedErrors(new MethodBased\OnlyPublicMethodChanged(
                                    new MethodBased\MultipleChecksOnAMethod(
                                        new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodBecameFinal()),
                                        new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodConcretenessChanged()),
                                        new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodScopeChanged()),
                                        new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodVisibilityReduced()),
                                        new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodFunctionDefinitionChanged(
                                            new FunctionBased\MultipleChecksOnAFunction(
                                                new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\FunctionBecameInternal()),
                                                new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterByReferenceChanged()),
                                                new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ReturnTypeByReferenceChanged()),
                                                new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\RequiredParameterAmountIncreased()),
                                                new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterDefaultValueChanged()),
                                                new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ReturnTypeCovarianceChanged(new TypeIsCovariant())),
                                                new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterTypeContravarianceChanged(new TypeIsContravariant()))
                                            )
                                        ))
                                    )
                                ))
                            ))
                        )
                    ))
                )
            ))),
            new InterfaceBased\SkipInterfaceBasedErrors(new InterfaceBased\ExcludeInternalInterface(new InterfaceBased\MultipleChecksOnAnInterface(
                new InterfaceBased\SkipInterfaceBasedErrors(new InterfaceBased\InterfaceBecameClass()),
                new InterfaceBased\SkipInterfaceBasedErrors(new InterfaceBased\InterfaceBecameTrait()),
                new InterfaceBased\SkipInterfaceBasedErrors(new InterfaceBased\AncestorRemoved()),
                new InterfaceBased\SkipInterfaceBasedErrors(new InterfaceBased\MethodAdded()),
                new InterfaceBased\SkipInterfaceBasedErrors(new InterfaceBased\UseClassBasedChecksOnAnInterface(
                    new ClassBased\MultipleChecksOnAClass(
                        new ClassBased\SkipClassBasedErrors(new ClassBased\ClassBecameInternal()),
                        new ClassBased\SkipClassBasedErrors(new ClassBased\ConstantRemoved()),
                        new ClassBased\SkipClassBasedErrors(new ClassBased\MethodRemoved()),
                        new ClassBased\SkipClassBasedErrors(new ClassBased\ConstantChanged(
                            new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\ClassConstantValueChanged())
                        )),
                        new ClassBased\SkipClassBasedErrors(new ClassBased\MethodChanged(
                            new MethodBased\MultipleChecksOnAMethod(
                                new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodScopeChanged()),
                                new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodFunctionDefinitionChanged(
                                    new FunctionBased\MultipleChecksOnAFunction(
                                        new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\FunctionBecameInternal()),
                                        new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterByReferenceChanged()),
                                        new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ReturnTypeByReferenceChanged()),
                                        new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\RequiredParameterAmountIncreased()),
                                        new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterDefaultValueChanged()),
                                        new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ReturnTypeCovarianceChanged(new TypeIsCovariant())),
                                        new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ReturnTypeChanged()),
                                        new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterTypeContravarianceChanged(new TypeIsContravariant())),
                                        new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterTypeChanged())
                                    )
                                ))
                            )
                        ))
                    )
                ))
            ))),
            new TraitBased\SkipTraitBasedErrors(new TraitBased\ExcludeInternalTrait(new TraitBased\MultipleChecksOnATrait(
                new TraitBased\SkipTraitBasedErrors(new TraitBased\TraitBecameInterface()),
                new TraitBased\SkipTraitBasedErrors(new TraitBased\TraitBecameClass()),
                new TraitBased\SkipTraitBasedErrors(new TraitBased\UseClassBasedChecksOnATrait(
                    new ClassBased\MultipleChecksOnAClass(
                        new ClassBased\SkipClassBasedErrors(new ClassBased\ClassBecameInternal()),
                        new ClassBased\SkipClassBasedErrors(new ClassBased\ConstantRemoved()),
                        new ClassBased\SkipClassBasedErrors(new ClassBased\PropertyRemoved()),
                        new ClassBased\SkipClassBasedErrors(new ClassBased\MethodRemoved()),
                        new ClassBased\SkipClassBasedErrors(new ClassBased\PropertyChanged(
                            new PropertyBased\MultipleChecksOnAProperty(
                                new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyBecameInternal()),
                                new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyDocumentedTypeChanged()),
                                new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyDefaultValueChanged()),
                                new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyVisibilityReduced()),
                                new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyScopeChanged())
                            )
                        )),
                        new ClassBased\SkipClassBasedErrors(new ClassBased\MethodChanged(
                            new MethodBased\MultipleChecksOnAMethod(
                                new MethodBased\MultipleChecksOnAMethod(
                                    new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodBecameFinal()),
                                    new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodConcretenessChanged()),
                                    new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodScopeChanged()),
                                    new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodVisibilityReduced()),
                                    new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodFunctionDefinitionChanged(
                                        new FunctionBased\MultipleChecksOnAFunction(
                                            new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\FunctionBecameInternal()),
                                            new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterByReferenceChanged()),
                                            new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ReturnTypeByReferenceChanged()),
                                            new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\RequiredParameterAmountIncreased()),
                                            new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterDefaultValueChanged()),
                                            new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ReturnTypeCovarianceChanged(new TypeIsCovariant())),
                                            new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ReturnTypeChanged()),
                                            new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterTypeContravarianceChanged(new TypeIsContravariant())),
                                            new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterTypeChanged())
                                        )
                                    ))
                                )
                            )
                        ))
                    )
                ))
            )))
        )
    );

    $application->add($apiCompareCommand);
    $application->setDefaultCommand('roave-backwards-compatibility-check:assert-backwards-compatible');

    $application->run($input, $output);
})();
