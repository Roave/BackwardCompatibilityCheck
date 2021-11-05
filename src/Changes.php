<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility;

use Countable;
use Generator;
use IteratorAggregate;
use Psl\Iter;
use Traversable;

/**
 * @implements IteratorAggregate<int, Change>
 */
final class Changes implements IteratorAggregate, Countable
{
    /** @var list<Change> */
    private array $bufferedChanges;

    /** @var iterable<int, Change>|null */
    private ?iterable $unBufferedChanges = null;

    /** @param list<Change> $bufferedChanges */
    private function __construct(array $bufferedChanges)
    {
        $this->bufferedChanges = $bufferedChanges;
    }

    public static function empty(): self
    {
        static $empty;

        if ($empty instanceof self) {
            return $empty;
        }

        return $empty = new self([]);
    }

    /** @param iterable<int, Change> $changes */
    public static function fromIterator(iterable $changes): self
    {
        $instance = new self([]);

        $instance->unBufferedChanges = $changes;

        return $instance;
    }

    public static function fromList(Change ...$changes): self
    {
        return new self($changes);
    }

    public function mergeWith(self $other): self
    {
        $instance = new self([]);

        $instance->bufferedChanges   = [];
        $instance->unBufferedChanges = (function () use ($other): Generator {
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
     * @return Traversable<int, Change>
     */
    public function getIterator(): iterable
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

    public function count(): int
    {
        return Iter\count($this->getIterator());
    }
}
