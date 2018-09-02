<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility;

use Countable;
use Generator;
use IteratorAggregate;
use function count;
use function iterator_to_array;

final class Changes implements IteratorAggregate, Countable
{
    /** @var Change[] */
    private $bufferedChanges;

    /** @var iterable|Change[]|null */
    private $unBufferedChanges;

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

        $empty->bufferedChanges = [];

        return $empty;
    }

    /** @param iterable|Change[] $changes */
    public static function fromIterator(iterable $changes) : self
    {
        $instance = new self();

        $instance->bufferedChanges   = [];
        $instance->unBufferedChanges = $changes;

        return $instance;
    }

    public static function fromList(Change ...$changes) : self
    {
        $instance = new self();

        $instance->bufferedChanges = $changes;

        return $instance;
    }

    public function mergeWith(self $other) : self
    {
        $instance = new self();

        $instance->bufferedChanges   = [];
        $instance->unBufferedChanges = (function () use ($other) : Generator {
            foreach ($this as $change) {
                yield $change;
            }

            foreach ($other as $change) {
                yield $change;
            }
        })();

        return $instance;
    }

    /**
     * {@inheritDoc}
     *
     * @return iterable|Change[]
     */
    public function getIterator() : iterable
    {
        foreach ($this->bufferedChanges as $change) {
            yield $change;
        }

        foreach ($this->unBufferedChanges ?? [] as $change) {
            $this->bufferedChanges[] = $change;

            yield $change;
        }

        $this->unBufferedChanges = null;
    }

    /**
     * {@inheritDoc}
     */
    public function count() : int
    {
        return count(iterator_to_array($this));
    }
}
