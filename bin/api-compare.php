<?php

declare(strict_types=1);

namespace Roave\ApiCompareCli;

use Roave\ApiCompare\Command;
use Roave\ApiCompare\Comparator;
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
                new Comparator\BackwardsCompatibility\ClassBased\PropertyRemoved(),
                new Comparator\BackwardsCompatibility\FunctionBased\ParameterDefaultValueChanged()
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
