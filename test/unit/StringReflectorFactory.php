<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility;

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Exception\EmptyPhpSourceCode;
use Roave\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

final class StringReflectorFactory
{
    /**
     * @throws EmptyPhpSourceCode
     */
    public function __invoke(string $sourceCode) : ClassReflector
    {
        $astLocator = (new BetterReflection())->astLocator();
        $stubber = new ReflectionSourceStubber();

        return new ClassReflector(
            new AggregateSourceLocator([
                new PhpInternalSourceLocator($astLocator, $stubber),
                new EvaledCodeSourceLocator($astLocator, $stubber),
                new StringSourceLocator($sourceCode, $astLocator),
            ])
        );
    }
}
