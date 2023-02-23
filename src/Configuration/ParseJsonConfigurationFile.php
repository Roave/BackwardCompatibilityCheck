<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Configuration;

use Psl\File;
use Psl\Json;
use Psl\Type;
use Roave\BackwardCompatibility\Baseline;
use RuntimeException;

final class ParseJsonConfigurationFile implements ParseConfigurationFile
{
    private const CONFIGURATION_FILENAME = '.roave-backward-compatibility-check.json';

    public function parse(string $currentDirectory): Configuration
    {
        $filename = $currentDirectory . '/' . self::CONFIGURATION_FILENAME;

        try {
            $jsonContents = File\read($filename);
        } catch (File\Exception\InvalidArgumentException) {
            return Configuration::default();
        }

        try {
            $configuration = Json\typed(
                $jsonContents,
                Type\shape(
                    ['baseline' => Type\optional(Type\vec(Type\string()))],
                ),
            );
        } catch (Json\Exception\DecodeException $exception) {
            throw new RuntimeException(
                'It was not possible to parse the configuration',
                previous: $exception,
            );
        }

        $baseline = $configuration['baseline'] ?? [];

        return Configuration::fromFile(
            Baseline::fromList(...$baseline),
            $filename,
        );
    }
}
