<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use RuntimeException;
use Symfony\Component\Process\Exception\RuntimeException as ProcessRuntimeException;
use Symfony\Component\Process\Process;
use Psl\Filesystem;
use Psl\Env;
use Psl\Str;

final class GitCheckoutRevisionToTemporaryPath implements PerformCheckoutOfRevision
{
    /** @var callable */
    private $uniquenessFunction;

    public function __construct(?callable $uniquenessFunction = null)
    {
        $this->uniquenessFunction = $uniquenessFunction ?? 'uniqid';
    }

    /**
     * @throws ProcessRuntimeException
     */
    public function checkout(CheckedOutRepository $sourceRepository, Revision $revision): CheckedOutRepository
    {
        $checkoutDirectory = $this->generateTemporaryPathFor($revision);

        (new Process(['git', 'clone', $sourceRepository, $checkoutDirectory]))->mustRun();
        (new Process(['git', 'checkout', $revision], $checkoutDirectory))->mustRun();

        return CheckedOutRepository::fromPath($checkoutDirectory);
    }

    /**
     * @throws ProcessRuntimeException
     */
    public function remove(CheckedOutRepository $checkedOutRepository): void
    {
        (new Process(['rm', '-rf', $checkedOutRepository]))->mustRun();
    }

    /**
     * @throws RuntimeException
     */
    private function generateTemporaryPathFor(Revision $revision): string
    {
        $uniquePathGenerator = $this->uniquenessFunction;
        $checkoutDirectory   = Env\temp_dir() . '/api-compare-' . $uniquePathGenerator($revision . '_');

        if (Filesystem\exists($checkoutDirectory)) {
            throw new RuntimeException(Str\format(
                'Tried to check out revision "%s" to directory "%s" which already exists',
                $revision->__toString(),
                $checkoutDirectory
            ));
        }

        return $checkoutDirectory;
    }
}
