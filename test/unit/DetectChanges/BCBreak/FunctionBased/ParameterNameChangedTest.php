<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ParameterNameChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_combine;
use function array_keys;
use function array_map;
use function assert;
use function iterator_to_array;

/** @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ParameterNameChanged */
final class ParameterNameChangedTest extends TestCase
{
    /**
     * @param string[] $expectedMessages
     *
     * @dataProvider functionsToBeTested
     */
    public function testDiffs(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction,
        array $expectedMessages,
    ): void {
        $changes = (new ParameterNameChanged())
            ->__invoke($fromFunction, $toFunction);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes)),
        );
    }

    /**
     * @return array<string, array{
     *     0: ReflectionMethod|ReflectionFunction,
     *     1: ReflectionMethod|ReflectionFunction,
     *     2: list<string>
     * }>
     */
    public function functionsToBeTested(): array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

function changed(int $a, int $b) {}
function untouched(int $a, int $b) {}
/** @no-named-arguments */
function changedButAnnotated(int $a, int $b) {}
/** @no-named-arguments */
function removingAnnotation(int $a, int $b) {}
function addingAnnotation(int $a, int $b) {}
function addedArgumentsShouldNotBeDetected($a, $b) {}
function removedArgumentsShouldNotBeDetected($a, $b, $c) {}
PHP
            ,
            $astLocator,
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

function changed(int $c, int $d) {}
function untouched(int $a, int $b) {}
/** @no-named-arguments */
function changedButAnnotated(int $c, int $d) {}
function removingAnnotation(int $a, int $b) {}
/** @no-named-arguments */
function addingAnnotation(int $a, int $b) {}
function addedArgumentsShouldNotBeDetected($a, $b, $c) {}
function removedArgumentsShouldNotBeDetected($a, $b) {}
PHP
            ,
            $astLocator,
        );

        $fromReflector = new DefaultReflector($fromLocator);
        $toReflector   = new DefaultReflector($toLocator);

        $functions = [
            'changed'                             => [
                '[BC] CHANGED: Parameter 0 of changed() changed name from a to c',
                '[BC] CHANGED: Parameter 1 of changed() changed name from b to d',
            ],
            'untouched'                           => [],
            'changedButAnnotated'                 => [],
            'removingAnnotation'                  => ['[BC] REMOVED: The @no-named-arguments annotation was removed from removingAnnotation()'],
            'addingAnnotation'                    => ['[BC] ADDED: The @no-named-arguments annotation was added from addingAnnotation()'],
            'addedArgumentsShouldNotBeDetected'   => [],
            'removedArgumentsShouldNotBeDetected' => [],
        ];

        return array_combine(
            array_keys($functions),
            array_map(
                static fn (string $function, array $errorMessages): array => [
                    $fromReflector->reflectFunction($function),
                    $toReflector->reflectFunction($function),
                    $errorMessages,
                ],
                array_keys($functions),
                $functions,
            ),
        );
    }

    public function testMethodWhereClassIsAnnotatedNoNamedParameterDoesNotCauseBreak(): void
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

/** @no-named-arguments */
class TheClass {
    public function theMethod(int $a) {}
}
PHP
            ,
            $astLocator,
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

/** @no-named-arguments */
class TheClass {
    public function theMethod(int $b) {}
}
PHP
            ,
            $astLocator,
        );

        $fromClassReflector = new DefaultReflector($fromLocator);
        $toClassReflector   = new DefaultReflector($toLocator);
        $fromMethod         = $fromClassReflector->reflectClass('TheClass')->getMethod('theMethod');
        $toMethod           = $toClassReflector->reflectClass('TheClass')->getMethod('theMethod');
        
        assert($fromMethod !== null);
        assert($toMethod !== null);

        $changes = (new ParameterNameChanged())($fromMethod, $toMethod);
        self::assertCount(0, $changes);
    }
}
