<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Command;

use Psl\Json;
use Psl\Type;
use Roave\BackwardCompatibility\Baseline;
use RuntimeException;

/** @psalm-immutable */
final class Configuration
{
    private function __construct(public readonly Baseline $baseline)
    {
    }

    public static function default(): self
    {
        return new self(Baseline::empty());
    }

    public static function fromJson(string $jsonContents): self
    {
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

        return new self(Baseline::fromList(...$baseline));
    }
}
