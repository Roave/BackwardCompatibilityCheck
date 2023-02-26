<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Configuration;

interface ParseConfigurationFile
{
    /** @throws InvalidConfigurationStructure When an incorrect file was found on the directory. */
    public function parse(string $currentDirectory): Configuration;
}
