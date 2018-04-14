<?php

declare(strict_types=1);

namespace Roave\ApiCompareCli;

use Roave\ApiCompare\Command;
use RuntimeException;
use Symfony\Component\Console\Application;
use function file_exists;

(function () {
    foreach ([__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../autoload.php'] as $autoload) {
        if (! file_exists($autoload)) {
            continue;
        }

        /** @noinspection PhpIncludeInspection */
        require $autoload;

        $apiCompareCommand = new Command\ApiCompare(
            new \Roave\ApiCompare\Git\GitCheckoutRevisionToTemporaryPath(),
            new \Roave\ApiCompare\Factory\DirectoryReflectorFactory()
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
