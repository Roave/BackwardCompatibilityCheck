<?php

declare(strict_types=1);

namespace Roave\ApiCompare\SourceLocator;

use Assert\Assert;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\AbstractSourceLocator;

final class StaticClassMapSourceLocator extends AbstractSourceLocator
{
    /** @var string[] */
    private $classMap;

    public function __construct(
        array $classMap,
        Locator $astLocator
    ) {
        parent::__construct($astLocator);

        Assert::that($classMap)->all()->file();
        Assert::that(array_keys($classMap))->all()->string()->notEmpty();

        $this->classMap = $classMap;
    }

    /**
     * {@inheritDoc}
     */
    protected function createLocatedSource(Identifier $identifier) : ?LocatedSource
    {
        if (! $identifier->isClass()) {
            return null;
        }

        $classFile = $this->classMap[$identifier->getName()] ?? null;

        if (null === $classFile) {
            return null;
        }

        return new LocatedSource(file_get_contents($classFile), $classFile);
    }
}
