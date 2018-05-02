<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use RuntimeException;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;
use Symfony\Component\Process\Process;
use function file_exists;
use function is_dir;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;

final class GitCheckoutRevisionToTemporaryPath implements PerformCheckoutOfRevision
{
    /** @var callable */
    private $uniquenessFunction;

    public function __construct(?callable $uniquenessFunction = null)
    {
        if ($uniquenessFunction === null) {
            $uniquenessFunction = function (string $nonUniqueThing) : string {
                return uniqid($nonUniqueThing, true);
            };
        }
        $this->uniquenessFunction = $uniquenessFunction;
    }

    /**
     * {@inheritDoc}
     * @throws ProcessRuntimeException
     */
    public function checkout(CheckedOutRepository $sourceRepository, Revision $revision) : CheckedOutRepository
    {
        $checkoutDirectory = $this->generateTemporaryPathFor($revision);

        (new Process(['git', 'clone', (string) $sourceRepository, $checkoutDirectory]))->mustRun();
        (new Process(['git', 'checkout', (string) $revision]))->setWorkingDirectory($checkoutDirectory)->mustRun();

        return CheckedOutRepository::fromPath($checkoutDirectory);
    }

    /**
     * {@inheritDoc}
     * @throws ProcessRuntimeException
     */
    public function remove(CheckedOutRepository $checkedOutRepository) : void
    {
        (new Process(['rm', '-rf', (string) $checkedOutRepository]))->mustRun();
    }

    /**
     * @throws RuntimeException
     */
    private function generateTemporaryPathFor(Revision $revision) : string
    {
        $uniquePathGenerator = $this->uniquenessFunction;
        $checkoutDirectory   = sys_get_temp_dir() . '/api-compare-' . $uniquePathGenerator((string) $revision . '_');

        if (file_exists($checkoutDirectory) || is_dir($checkoutDirectory)) {
            throw new RuntimeException(sprintf(
                'Tried to check out revision %s to directory %s which already exists',
                (string) $revision,
                $checkoutDirectory
            ));
        }

        return $checkoutDirectory;
    }
}
