<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility;

use ArrayIterator;
use Countable;
use Generator;
use IteratorAggregate;
use function count;

final class Changes implements IteratorAggregate, Countable
{
    /** @var Change[] */
    private $changes;

    /** Generator|null */
    private $generator;

    private function __construct()
    {
    }

    public static function empty() : self
    {
        static $empty;

        if ($empty) {
            return $empty;
        }

        $empty = new self();

        $empty->changes = [];

        return $empty;
    }

    /** @param iterable|Change[] $changes */
    public static function fromIterator(iterable $changes) : self
    {
        $instance = new self();

        $instance->changes   = [];
        $instance->generator = (function () use ($changes) : Generator {
            foreach ($changes as $change) {
                yield $change;
            }
        })();

        return $instance;
    }

    /** @param iterable|Change[] $changes */
    public function mergeWithIterator(iterable $changes) : self
    {
        $instance = new self();

        $instance->changes   = [];
        $instance->generator = (function () use ($changes) : Generator {
            foreach ($this as $change) {
                yield $change;
            }

            foreach ($changes as $change) {
                yield $change;
            }
        })();

        return $instance;
    }

    public static function fromList(Change ...$changes) : self
    {
        $instance = new self();

        $instance->changes = $changes;

        return $instance;
    }

    public function mergeWith(self $other) : self
    {
        return $this->mergeWithIterator($other->getIterator());
    }

    /**
     * {@inheritDoc}
     *
     * @return iterable|Change[]
     */
    public function getIterator() : iterable
    {
        foreach ($this->changes as $change) {
            yield $change;
        }

        foreach ($this->generator ?? [] as $change) {
            $this->changes[] = $change;

            yield $change;
        }

        $this->generator = null;
    }

    /**
     * {@inheritDoc}
     */
    public function count() : int
    {
        return count(iterator_to_array($this));
    }
}
