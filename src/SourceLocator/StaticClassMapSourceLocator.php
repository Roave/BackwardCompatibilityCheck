<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\SourceLocator;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\AbstractSourceLocator;
use Psl\Type;
use Psl\Dict;
use Psl\Filesystem;
use Psl\Iter;
use Psl;

final class StaticClassMapSourceLocator extends AbstractSourceLocator
{
    /** @var array<string, string> */
    private array $classMap;

     /**
      * @param array<string, string> $classMap map of class => file. Every file must exist,
      *                                        every key must be non-empty
      */
    public function __construct(
        array $classMap,
        Locator $astLocator
    ) {
        parent::__construct($astLocator);

        $realPaths = Dict\map($classMap, static function(string $file): string {
            return Type\string()->assert(Filesystem\canonicalize($file));
        });
        
        Psl\invariant(Iter\all($realPaths, static function(string $file): bool {
            return Filesystem\is_file($file);
        }), 'Invalid class-map.');

        $this->classMap = Type\dict(Type\non_empty_string(), Type\string())->coerce($realPaths);
    }

    protected function createLocatedSource(Identifier $identifier): ?LocatedSource
    {
        if (! $identifier->isClass()) {
            return null;
        }

        $classFile = $this->classMap[$identifier->getName()] ?? null;

        if ($classFile === null) {
            return null;
        }

        return new LocatedSource(Filesystem\read_file($classFile), $classFile);
    }
}
