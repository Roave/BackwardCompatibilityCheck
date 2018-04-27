<?php

declare(strict_types=1);

namespace Roave\ApiCompare;

use ArrayIterator;
use Assert\Assert;
use Countable;
use IteratorAggregate;
use function array_merge;
use function count;

final class Changes implements IteratorAggregate, Countable
{
    /** @var Change[] */
    private $changes = [];

    private function __construct()
    {
    }

    public static function empty() : self
    {
        return new self();
    }

    /**
     * @param Change[] $changes
     * @return Changes
     */
    public static function fromArray(array $changes) : self
    {
        Assert::that($changes)->all()->isInstanceOf(Change::class);
        $instance          = self::empty();
        $instance->changes = $changes;
        return $instance;
    }

    public function mergeWith(self $other) : self
    {
        $instance = new self();

        $instance->changes = array_merge($this->changes, $other->changes);

        return $instance;
    }

    public function withAddedChange(Change $change) : self
    {
        $new            = clone $this;
        $new->changes[] = $change;
        return $new;
    }

    /**
     * {@inheritDoc}
     *
     * @return ArrayIterator|Change[]
     */
    public function getIterator() : ArrayIterator
    {
        return new ArrayIterator($this->changes);
    }

    /**
     * {@inheritDoc}
     */
    public function count() : int
    {
        return count($this->changes);
    }
}
