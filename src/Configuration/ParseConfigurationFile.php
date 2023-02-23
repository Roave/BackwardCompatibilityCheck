<?php
declare(strict_types=1);

namespace Roave\BackwardCompatibility\Configuration;

interface ParseConfigurationFile
{
    public function parse(string $currentDirectory): Configuration;
}
