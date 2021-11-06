<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility;

use Psl\Str;
use Throwable;

/** @psalm-immutable */
final class Change
{
    private const ADDED   = 'added';
    private const CHANGED = 'changed';
    private const REMOVED = 'removed';
    private const SKIPPED = 'skipped';

    /** @psalm-var self::* */
    private string $modificationType;

    private string $description;

    private bool $isBcBreak;

    /** @psalm-param self::* $modificationType */
    private function __construct(string $modificationType, string $description, bool $isBcBreak)
    {
        $this->modificationType = $modificationType;
        $this->description      = $description;
        $this->isBcBreak        = $isBcBreak;
    }

    /** @psalm-pure */
    public static function added(string $description, bool $isBcBreak): self
    {
        return new self(self::ADDED, $description, $isBcBreak);
    }

    /** @psalm-pure */
    public static function changed(string $description, bool $isBcBreak): self
    {
        return new self(self::CHANGED, $description, $isBcBreak);
    }

    /** @psalm-pure */
    public static function removed(string $description, bool $isBcBreak): self
    {
        return new self(self::REMOVED, $description, $isBcBreak);
    }

    /** @psalm-pure */
    public static function skippedDueToFailure(Throwable $failure): self
    {
        // @TODO Note: we may consider importing the full exception for better printing later on
        return new self(self::SKIPPED, $failure->getMessage(), true);
    }

    public function isAdded(): bool
    {
        return $this->modificationType === self::ADDED;
    }

    public function isRemoved(): bool
    {
        return $this->modificationType === self::REMOVED;
    }

    public function isChanged(): bool
    {
        return $this->modificationType === self::CHANGED;
    }

    public function isSkipped(): bool
    {
        return $this->modificationType === self::SKIPPED;
    }

    public function __toString(): string
    {
        return Str\format(
            '%s%s: %s',
            $this->isBcBreak ? '[BC] ' : '     ',
            Str\uppercase($this->modificationType),
            $this->description
        );
    }
}
