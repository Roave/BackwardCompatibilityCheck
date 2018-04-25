<?php

declare(strict_types=1);

namespace Roave\ApiCompare\LocateDependencies;

use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

interface LocateDependencies
{
    public function __invoke(string $installationPath) : SourceLocator;
}
