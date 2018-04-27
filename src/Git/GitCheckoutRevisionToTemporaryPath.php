<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use function sys_get_temp_dir;

final class GitCheckoutRevisionToTemporaryPath implements PerformCheckoutOfRevision
{
    /**
     * {@inheritDoc}
     * @throws RuntimeException
     */
    public function checkout(CheckedOutRepository $sourceRepository, Revision $revision) : CheckedOutRepository
    {
        $checkoutDirectory = sys_get_temp_dir() . '/api-compare-' . (string) $revision;

        (new Process(['git', 'clone', (string) $sourceRepository, $checkoutDirectory]))->mustRun();
        (new Process(['git', 'checkout', (string) $revision]))->setWorkingDirectory($checkoutDirectory)->mustRun();

        return CheckedOutRepository::fromPath($checkoutDirectory);
    }

    /**
     * {@inheritDoc}
     * @throws RuntimeException
     */
    public function remove(CheckedOutRepository $checkedOutRepository) : void
    {
        (new Process(['rm', '-rf', (string) $checkedOutRepository]))->mustRun();
    }
}
