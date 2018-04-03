<?php
declare(strict_types=1);

use Roave\ApiCompare\Command;
use Symfony\Component\Console\Application;

$foundAutoload = false;
foreach ([__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../autoload.php'] as $autoload) {
    if (file_exists($autoload)) {
        /** @noinspection PhpIncludeInspection */
        require $autoload;
        $foundAutoload = true;
        break;
    }
}

if (!$foundAutoload) {
    throw new \RuntimeException('Could not find Composer autoload.php');
}

$apiCompareCommand = new Command\ApiCompare(
    new \Roave\ApiCompare\Git\GitCheckoutRevisionToTemporaryPath(),
    new \Roave\ApiCompare\Factory\DirectoryReflectorFactory()
);

$application = new Application();
$application->add($apiCompareCommand);
$application->setDefaultCommand($apiCompareCommand->getName());

/** @noinspection PhpUnhandledExceptionInspection */
$application->run();
