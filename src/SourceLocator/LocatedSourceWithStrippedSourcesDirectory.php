<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\SourceLocator;

use Roave\BetterReflection\SourceLocator\Located\LocatedSource;

/** @internal */
final class LocatedSourceWithStrippedSourcesDirectory extends LocatedSource
{
    public function __construct(
        private LocatedSource $next,
        private string $sourcesDirectory
    ) {
    }

    // @TODO test that all methods are covered (use reflection)
    public function getSource(): string
    {
        return $this->next->getSource();
    }

    public function getName(): ?string
    {
        return $this->next->getName();
    }

    public function getFileName(): ?string
    {
        $fileName = $this->next->getFileName();

        if (null === $fileName || ! str_starts_with($fileName, $this->sourcesDirectory)) {
            return $fileName;
        }

        return substr_replace($fileName, '', 0, strlen($this->sourcesDirectory));
    }

    public function isInternal(): bool
    {
        return $this->next->isInternal();
    }

    public function getExtensionName(): ?string
    {
        return $this->next->getExtensionName();
    }

    public function isEvaled(): bool
    {
        return $this->next->isEvaled();
    }

    public function getAliasName(): ?string
    {
        return $this->next->getAliasName();
    }
}
