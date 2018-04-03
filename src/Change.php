<?php
declare(strict_types=1);

namespace Roave\ApiCompare;

use Assert\Assert;

final class Change
{
    private const ADDED = 'added';
    private const CHANGED = 'changed';
    private const REMOVED = 'removed';

    /**
     * @var string[]
     */
    private static $validModificationTypes = [self::ADDED, self::CHANGED, self::REMOVED];

    /**
     * @var string
     */
    private $modificationType;

    /**
     * @var string
     */
    private $description;

    /**
     * @var bool
     */
    private $isBcBreak;

    private function __construct(string $modificationType, string $description, bool $isBcBreak)
    {
        Assert::that($modificationType)->inArray(self::$validModificationTypes);
        $this->modificationType = $modificationType;
        $this->description = $description;
        $this->isBcBreak = $isBcBreak;
    }

    public static function added(string $description, bool $isBcBreak): self
    {
        return new self(self::ADDED, $description, $isBcBreak);
    }

    public static function changed(string $description, bool $isBcBreak): self
    {
        return new self(self::CHANGED, $description, $isBcBreak);
    }

    public static function removed(string $description, bool $isBcBreak): self
    {
        return new self(self::REMOVED, $description, $isBcBreak);
    }

    public function __toString(): string
    {
        return sprintf(
            '%s%s: %s',
            $this->isBcBreak ? '[BC] ' : '     ',
            strtoupper($this->modificationType),
            $this->description
        );
    }
}
