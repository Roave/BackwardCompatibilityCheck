<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Formatter;

use Roave\BackwardCompatibility\Changes;

interface OutputFormatter
{
    public function write(Changes $changes): void;
}
