<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility;

use Throwable;
use function Safe\sprintf;
use function strtoupper;

/**
 * @todo this class probably needs subclassing or being turned into an interface
 */
final class Change
{
    private const ADDED   = 'added';
    private const CHANGED = 'changed';
    private const REMOVED = 'removed';
    private const SKIPPED = 'skipped';

    /** @var string */
    private $modificationType;

    /** @var string */
    private $description;

    /** @var bool */
    private $isBcBreak;

    private function __construct(string $modificationType, string $description, bool $isBcBreak)
    {
        $this->modificationType = $modificationType;
        $this->description      = $description;
        $this->isBcBreak        = $isBcBreak;
    }

    public static function added(string $description, bool $isBcBreak) : self
    {
        return new self(self::ADDED, $description, $isBcBreak);
    }

    public static function changed(string $description, bool $isBcBreak) : self
    {
        return new self(self::CHANGED, $description, $isBcBreak);
    }

    public static function removed(string $description, bool $isBcBreak) : self
    {
        return new self(self::REMOVED, $description, $isBcBreak);
    }

    public static function skippedDueToFailure(Throwable $failure) : self
    {
        // @TODO Note: we may consider importing the full exception for better printing later on
        return new self(self::SKIPPED, $failure->getMessage(), true);
    }

    public function isAdded() : bool
    {
        return $this->modificationType === self::ADDED;
    }

    public function isRemoved() : bool
    {
        return $this->modificationType === self::REMOVED;
    }

    public function isChanged() : bool
    {
        return $this->modificationType === self::CHANGED;
    }

    public function isSkipped() : bool
    {
        return $this->modificationType === self::SKIPPED;
    }

    public function __toString() : string
    {
        return sprintf(
            '%s%s: %s',
            $this->isBcBreak ? '[BC] ' : '     ',
            strtoupper($this->modificationType),
            $this->description
        );
    }
}
