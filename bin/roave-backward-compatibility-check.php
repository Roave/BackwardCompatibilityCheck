<?php

declare(strict_types=1);

namespace Roave\ApiCompareCli;

use Composer\Composer;
use Composer\Factory;
use Composer\Installer;
use Composer\IO\ConsoleIO;
use PackageVersions\Versions;
use Psl\Type;
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
use Roave\BackwardCompatibility\Git\PickLastVersionFromCollection;
use Roave\BackwardCompatibility\LocateDependencies\LocateDependenciesViaComposer;
use Roave\BackwardCompatibility\LocateSources\LocateSourcesViaComposerJson;
use Roave\BetterReflection\BetterReflection;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

use function error_reporting;
use function file_exists;

use const E_ALL;
use const E_DEPRECATED;

(static function (): void {
    error_reporting(E_ALL & ~E_DEPRECATED);

    (static function (): void {
        $autoloaderLocations = [
            __DIR__ . '/../vendor/autoload.php', // Installed by cloning the project and running `composer install`
            __DIR__ . '/../../../autoload.php',  // Installed via `composer require`
        ];

        foreach ($autoloaderLocations as $autoload) {
            if (file_exists($autoload)) {
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
        new PickLastVersionFromCollection(),
        new LocateDependenciesViaComposer(
            static function (string $installationPath) use ($composerIo): Installer {
                return Installer::create(
                    $composerIo,
                    Type\instance_of(Composer::class)
                        ->assert(
                            (new Factory())->createComposer(
                                $composerIo,
                                null,
                                true,
                                $installationPath,
                            ),
                        ),
                );
            },
            $astLocator,
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
                    new ClassBased\SkipClassBasedErrors(new ClassBased\EnumCasesChanged()),
                    new ClassBased\SkipClassBasedErrors(new ClassBased\OpenClassChanged(
                        new ClassBased\MultipleChecksOnAClass(
                            new ClassBased\SkipClassBasedErrors(new ClassBased\ConstantChanged(
                                new ClassConstantBased\MultipleChecksOnAClassConstant(
                                    new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\OnlyPublicClassConstantChanged(
                                        new ClassConstantBased\MultipleChecksOnAClassConstant(
                                            new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\ClassConstantVisibilityReduced()),
                                            new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\ClassConstantValueChanged()),
                                        ),
                                    )),
                                    new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\OnlyProtectedClassConstantChanged(
                                        new ClassConstantBased\MultipleChecksOnAClassConstant(
                                            new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\ClassConstantVisibilityReduced()),
                                            new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\ClassConstantValueChanged()),
                                        ),
                                    )),
                                ),
                            )),
                            new ClassBased\SkipClassBasedErrors(new ClassBased\PropertyChanged(
                                new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\ExcludeInternalProperty(new PropertyBased\MultipleChecksOnAProperty(
                                    new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\OnlyPublicPropertyChanged(
                                        new PropertyBased\MultipleChecksOnAProperty(
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyBecameInternal()),
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyTypeChanged(new TypeIsContravariant(), new TypeIsCovariant())),
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyDefaultValueChanged()),
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyVisibilityReduced()),
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyScopeChanged()),
                                        ),
                                    )),
                                    new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\OnlyProtectedPropertyChanged(
                                        new PropertyBased\MultipleChecksOnAProperty(
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyBecameInternal()),
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyTypeChanged(new TypeIsContravariant(), new TypeIsCovariant())),
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyDefaultValueChanged()),
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyVisibilityReduced()),
                                            new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyScopeChanged()),
                                        ),
                                    )),
                                ))),
                            )),
                            new ClassBased\SkipClassBasedErrors(new ClassBased\MethodChanged(
                                new MethodBased\SkipMethodBasedErrors(new MethodBased\ExcludeInternalMethod(new MethodBased\MultipleChecksOnAMethod(
                                    new MethodBased\SkipMethodBasedErrors(new MethodBased\OnlyPublicMethodChanged(
                                        new MethodBased\MultipleChecksOnAMethod(
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodBecameFinal()),
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodConcretenessChanged()),
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodScopeChanged()),
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodVisibilityReduced()),
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodParameterAdded()),
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
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterTypeChanged()),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterNameChanged()),
                                                ),
                                            )),
                                        ),
                                    )),
                                    new MethodBased\SkipMethodBasedErrors(new MethodBased\OnlyProtectedMethodChanged(
                                        new MethodBased\MultipleChecksOnAMethod(
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodBecameFinal()),
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodConcretenessChanged()),
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodScopeChanged()),
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodVisibilityReduced()),
                                            new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodParameterAdded()),
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
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterTypeChanged()),
                                                    new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterNameChanged()),
                                                ),
                                            )),
                                        ),
                                    )),
                                ))),
                            )),
                        ),
                    )),
                    new ClassBased\SkipClassBasedErrors(new ClassBased\FinalClassChanged(
                        new ClassBased\MultipleChecksOnAClass(
                            new ClassBased\SkipClassBasedErrors(new ClassBased\ConstantChanged(
                                new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\OnlyPublicClassConstantChanged(
                                    new ClassConstantBased\MultipleChecksOnAClassConstant(
                                        new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\ClassConstantVisibilityReduced()),
                                        new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\ClassConstantValueChanged()),
                                    ),
                                )),
                            )),
                            new ClassBased\SkipClassBasedErrors(new ClassBased\PropertyChanged(
                                new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\OnlyPublicPropertyChanged(
                                    new PropertyBased\MultipleChecksOnAProperty(
                                        new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyBecameInternal()),
                                        new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyTypeChanged(new TypeIsContravariant(), new TypeIsCovariant())),
                                        new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyDefaultValueChanged()),
                                        new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyVisibilityReduced()),
                                        new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyScopeChanged()),
                                    ),
                                )),
                            )),
                            new ClassBased\SkipClassBasedErrors(new ClassBased\MethodChanged(
                                new MethodBased\SkipMethodBasedErrors(new MethodBased\OnlyPublicMethodChanged(new MethodBased\ExcludeInternalMethod(
                                    new MethodBased\MultipleChecksOnAMethod(
                                        new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodBecameFinal()),
                                        new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodConcretenessChanged()),
                                        new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodScopeChanged()),
                                        new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodParameterAdded()),
                                        new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodVisibilityReduced()),
                                        new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodFunctionDefinitionChanged(
                                            new FunctionBased\MultipleChecksOnAFunction(
                                                new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\FunctionBecameInternal()),
                                                new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterByReferenceChanged()),
                                                new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ReturnTypeByReferenceChanged()),
                                                new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\RequiredParameterAmountIncreased()),
                                                new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterDefaultValueChanged()),
                                                new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ReturnTypeCovarianceChanged(new TypeIsCovariant())),
                                                new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterTypeContravarianceChanged(new TypeIsContravariant())),
                                            ),
                                        )),
                                    ),
                                ))),
                            )),
                        ),
                    )),
                ),
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
                            new ClassConstantBased\SkipClassConstantBasedErrors(new ClassConstantBased\ClassConstantValueChanged()),
                        )),
                        new ClassBased\SkipClassBasedErrors(new ClassBased\MethodChanged(
                            new MethodBased\MultipleChecksOnAMethod(
                                new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodScopeChanged()),
                                new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodParameterAdded()),
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
                                        new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterTypeChanged()),
                                        new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterNameChanged()),
                                    ),
                                )),
                            ),
                        )),
                    ),
                )),
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
                                new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyTypeChanged(new TypeIsContravariant(), new TypeIsCovariant())),
                                new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyDefaultValueChanged()),
                                new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyVisibilityReduced()),
                                new PropertyBased\SkipPropertyBasedErrors(new PropertyBased\PropertyScopeChanged()),
                            ),
                        )),
                        new ClassBased\SkipClassBasedErrors(new ClassBased\MethodChanged(
                            new MethodBased\MultipleChecksOnAMethod(
                                new MethodBased\MultipleChecksOnAMethod(
                                    new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodBecameFinal()),
                                    new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodConcretenessChanged()),
                                    new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodScopeChanged()),
                                    new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodVisibilityReduced()),
                                    new MethodBased\SkipMethodBasedErrors(new MethodBased\MethodParameterAdded()),
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
                                            new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterTypeChanged()),
                                            new FunctionBased\SkipFunctionBasedErrors(new FunctionBased\ParameterNameChanged()),
                                        ),
                                    )),
                                ),
                            ),
                        )),
                    ),
                )),
            ))),
        ),
    );

    $application->add($apiCompareCommand);
    $application->setDefaultCommand('roave-backwards-compatibility-check:assert-backwards-compatible');

    $output->getErrorOutput()
        ->writeln(
            <<<'MARKDOWN'
## 🇷🇺 Российским гражданам

В Украине сейчас идет война. Силами РФ наносятся удары по гражданской инфраструктуре в [Харькове][1], [Киеве][2], [Чернигове][3], [Сумах][4], [Ирпене][5] и десятках других городов. Гибнут люди - и гражданское население, и военные, в том числе российские призывники, которых бросили воевать. Чтобы лишить собственный народ доступа к информации, правительство РФ запретило называть войну войной, закрыло независимые СМИ и принимает сейчас ряд диктаторских законов. Эти законы призваны заткнуть рот всем, кто против войны. За обычный призыв к миру сейчас можно получить несколько лет тюрьмы.

Не молчите! Молчание - знак вашего согласия с политикой российского правительства.
**Вы можете сделать выбор НЕ МОЛЧАТЬ.**

---

## 🇺🇸 To people of Russia

There is a war in Ukraine right now. The forces of the Russian Federation are attacking civilian infrastructure in [Kharkiv][1], [Kyiv][2], [Chernihiv][3], [Sumy][4], [Irpin][5] and dozens of other cities. People are dying – both civilians and military, including Russian conscripts who were thrown into the fighting. In order to deprive its own people of access to information, the government of the Russian Federation has forbidden calling a war a war, shut down independent media and is passing a number of dictatorial laws. These laws are meant to silence all those who are against war. You can be jailed for multiple years for simply calling for peace.

Do not be silent! Silence is a sign that you accept the Russian government's policy.
**You can choose NOT TO BE SILENT.**

[1]: <https://cloudfront-us-east-2.images.arcpublishing.com/reuters/P7K2MSZDGFMIJPDD7CI2GIROJI.jpg> "Kharkiv under attack"
[2]: <https://gdb.voanews.com/01bd0000-0aff-0242-fad0-08d9fc92c5b3_cx0_cy5_cw0_w1023_r1_s.jpg> "Kyiv under attack"
[3]: <https://ichef.bbci.co.uk/news/976/cpsprodpb/163DD/production/_123510119_hi074310744.jpg> "Chernihiv under attack"
[4]: <https://www.youtube.com/watch?v=8K-bkqKKf2A> "Sumy under attack"
[5]: <https://cloudfront-us-east-2.images.arcpublishing.com/reuters/K4MTMLEHTRKGFK3GSKAT4GR3NE.jpg> "Irpin under attack"

<fg=#0057b7>#StandWith</><fg=#ffd700>Ukraine</>


MARKDOWN,
        );

    $application->run($input, $output);
})();
