<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Configuration;

use LibXMLError;
use Psl\Str;
use RuntimeException;

use const PHP_EOL;

/** @internal */
final class InvalidConfigurationStructure extends RuntimeException
{
    /** @param list<LibXMLError> $errors */
    public static function fromLibxmlErrors(array $errors): self
    {
        $message = 'The provided configuration is invalid, errors:' . PHP_EOL;

        foreach ($errors as $error) {
            $message .= Str\format(
                ' - [Line %d] %s' . PHP_EOL,
                $error->line,
                Str\trim($error->message),
            );
        }

        return new self($message);
    }
}
