<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Command;

use Roave\BackwardCompatibility\Git\CheckedOutRepository;
use Roave\BackwardCompatibility\Git\PerformCheckoutOfRevision;
use Roave\BackwardCompatibility\Git\Revision;
use SplObjectStorage;

final class PerformCheckoutOfRevisionForTests implements PerformCheckoutOfRevision
{
    /** @var SplObjectStorage<CheckedOutRepository, int> */
    private SplObjectStorage $checkedOutRepositories;

    public function __construct()
    {
        $this->checkedOutRepositories = new SplObjectStorage();
    }

    public function checkout(CheckedOutRepository $sourceRepository, Revision $revision): CheckedOutRepository
    {
        if (! isset($this->checkedOutRepositories[$sourceRepository])) {
            $this->checkedOutRepositories[$sourceRepository] = 0;
        }

        $this->checkedOutRepositories[$sourceRepository] += 1;

        return $sourceRepository;
    }

    public function remove(CheckedOutRepository $checkedOutRepository): void
    {
        $this->checkedOutRepositories[$checkedOutRepository] -= 1;

        if ($this->checkedOutRepositories[$checkedOutRepository] !== 0) {
            return;
        }

        unset($this->checkedOutRepositories[$checkedOutRepository]);
    }

    public function nonRemovedRepositoryCount(): int
    {
        $sum = 0;

        foreach ($this->checkedOutRepositories as $repository) {
            $sum += $this->checkedOutRepositories[$repository];
        }

        return $sum;
    }
}
