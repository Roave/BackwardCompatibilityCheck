<?php

declare(strict_types=1);

namespace Roave\ApiCompare\Git;

interface PerformCheckoutOfRevision
{
    public function checkout(CheckedOutRepository $sourceRepository, Revision $revision) : CheckedOutRepository;

    public function remove(CheckedOutRepository $checkedOutRepository) : void;
}
