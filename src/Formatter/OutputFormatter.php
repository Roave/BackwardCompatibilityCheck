<?php
declare(strict_types=1);

namespace Roave\ApiCompare\Formatter;

use Roave\ApiCompare\Changes;

interface OutputFormatter
{
    public function write(Changes $changes) : void;
}
