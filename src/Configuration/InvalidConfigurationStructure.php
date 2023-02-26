<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Configuration;

use LibXMLError;
use RuntimeException;

use function sprintf;
use function trim;

use const PHP_EOL;

/** @internal */
final class InvalidConfigurationStructure extends RuntimeException
{
    /** @param list<LibXMLError> $errors */
    public static function fromLibxmlErrors(array $errors): self
    {
        $message = 'The provided configuration is invalid, errors:' . PHP_EOL;

        foreach ($errors as $error) {
            $message .= sprintf(
                ' - [Line %d] %s' . PHP_EOL,
                $error->line,
                trim($error->message),
            );
        }

        return new self($message);
    }
}
