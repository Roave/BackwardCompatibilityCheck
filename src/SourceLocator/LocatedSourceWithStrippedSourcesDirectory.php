<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\SourceLocator;

use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

use function str_starts_with;
use function strlen;
use function substr_replace;

/**
 * @internal
 *
 * @psalm-immutable
 */
final class LocatedSourceWithStrippedSourcesDirectory extends LocatedSource
{
    public function __construct(
        private LocatedSource $next,
        private string $sourcesDirectory,
    ) {
    }

    /** @psalm-external-mutation-free */
    public function getSource(): string
    {
        return $this->next->getSource();
    }

    /** @psalm-external-mutation-free */
    public function getName(): string|null
    {
        return $this->next->getName();
    }

    /**
     * @psalm-external-mutation-free
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function getFileName(): string
    {
        $fileName = (string) $this->next->getFileName();

        if (! str_starts_with($fileName, $this->sourcesDirectory)) {
            return $fileName;
        }

        return substr_replace($fileName, '', 0, strlen($this->sourcesDirectory));
    }

    /** @psalm-external-mutation-free */
    public function isInternal(): bool
    {
        return $this->next->isInternal();
    }

    /** @psalm-external-mutation-free */
    public function getExtensionName(): string|null
    {
        return $this->next->getExtensionName();
    }

    /** @psalm-external-mutation-free */
    public function isEvaled(): bool
    {
        return $this->next->isEvaled();
    }

    /** @psalm-external-mutation-free */
    public function getAliasName(): string|null
    {
        return $this->next->getAliasName();
    }
}
