<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Command;

use Psl\Str;
use Psl\Type;
use Roave\BackwardCompatibility\Configuration\Configuration;
use Roave\BackwardCompatibility\Configuration\ParseConfigurationFile;
use Roave\BackwardCompatibility\Configuration\ParseXmlConfigurationFile;
use Symfony\Component\Console\Output\OutputInterface;

/** @internal */
final class DetermineConfigurationFromFilesystem
{
    public function __construct(
        private readonly ParseConfigurationFile $parser = new ParseXmlConfigurationFile(),
    ) {
    }

    public function __invoke(
        string $currentDirectory,
        OutputInterface $stdErr,
    ): Configuration {
        $configuration = $this->parser->parse($currentDirectory);

        if ($configuration->filename !== null) {
            $stdErr->writeln(Str\format(
                'Using "%s" as configuration file',
                Type\string()->coerce($configuration->filename),
            ));
        }

        return $configuration;
    }
}
