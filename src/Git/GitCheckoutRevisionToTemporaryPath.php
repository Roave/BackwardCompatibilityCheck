<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Git;

use Psl\Env;
use Psl\Filesystem;
use Psl\Shell;
use Psl\Str;
use RuntimeException;

final class GitCheckoutRevisionToTemporaryPath implements PerformCheckoutOfRevision
{
    /** @var callable */
    private $uniquenessFunction;

    public function __construct(?callable $uniquenessFunction = null)
    {
        $this->uniquenessFunction = $uniquenessFunction ?? 'uniqid';
    }

    /**
     * @throws Shell\Exception\RuntimeException
     */
    public function checkout(CheckedOutRepository $sourceRepository, Revision $revision): CheckedOutRepository
    {
        $checkoutDirectory = $this->generateTemporaryPathFor($revision);

        Shell\execute('git', ['clone', $sourceRepository->__toString(), $checkoutDirectory]);
        Shell\execute('git', ['checkout', $revision->__toString()], $checkoutDirectory);

        return CheckedOutRepository::fromPath($checkoutDirectory);
    }

    /**
     * @throws Shell\Exception\RuntimeException
     */
    public function remove(CheckedOutRepository $checkedOutRepository): void
    {
        Shell\execute('rm', ['-rf', $checkedOutRepository->__toString()]);
    }

    /**
     * @throws RuntimeException
     */
    private function generateTemporaryPathFor(Revision $revision): string
    {
        $uniquePathGenerator = $this->uniquenessFunction;
        $checkoutDirectory   = Env\temp_dir() . '/api-compare-' . $uniquePathGenerator($revision->__toString());

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
