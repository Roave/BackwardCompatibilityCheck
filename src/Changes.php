<?php
declare(strict_types=1);

namespace Roave\ApiCompare;

use ArrayIterator;
use Assert\Assert;
use IteratorAggregate;

final class Changes implements IteratorAggregate
{
    /** @var Change[] */
    private $changes = [];

    private function __construct()
    {
    }

    public static function new(): self
    {
        return new self();
    }

    public static function fromArray(array $changes): self
    {
        Assert::that($changes)->all()->isInstanceOf(Change::class);
        $instance = self::new();
        $instance->changes = $changes;
        return $instance;
    }

    public function withAddedChange(Change $change): self
    {
        $new = clone $this;
        $new->changes[] = $change;
        return $new;
    }

    /**
     * {@inheritDoc}
     *
     * @return Change[]
     */
    public function getIterator() : ArrayIterator
    {
        return new ArrayIterator($this->changes);
    }
}
