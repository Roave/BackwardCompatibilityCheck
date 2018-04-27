<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\SourceLocator;

use Assert\Assert;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\AbstractSourceLocator;
use function array_keys;
use function array_map;
use function file_get_contents;

final class StaticClassMapSourceLocator extends AbstractSourceLocator
{
    /** @var string[] */
    private $classMap;

    /** @param string[] $classMap of class to file path. Every file must exist, every key must be non-empty */
    public function __construct(
        array $classMap,
        Locator $astLocator
    ) {
        parent::__construct($astLocator);

        $realPaths = array_map('realpath', $classMap);

        Assert::that($classMap)->all()->file();
        Assert::that(array_keys($classMap))->all()->string()->notEmpty();

        $this->classMap = $realPaths;
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

        if ($classFile === null) {
            return null;
        }

        return new LocatedSource(file_get_contents($classFile), $classFile);
    }
}
