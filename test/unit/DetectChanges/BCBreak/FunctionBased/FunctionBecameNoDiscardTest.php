<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\FunctionBecameNoDiscard;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

#[CoversClass(FunctionBecameNoDiscard::class)]
final class FunctionBecameNoDiscardTest extends TestCase
{
    /** @param string[] $expectedMessages */
    #[DataProvider('functionsToBeTested')]
    public function testDiffs(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction,
        array $expectedMessages,
    ): void {
        $changes = (new FunctionBecameNoDiscard())($fromFunction, $toFunction);

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
    public static function functionsToBeTested(): array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace TheNamespace;

function untouched() {}
function becameNoDiscard() {}
#[\NoDiscard]
function noLongerNoDiscard() {}
#[\NoDiscard]
function stillNoDiscard() {}
function becameNoDiscardWithDifferentCasing() {}
function becameNoDiscardViaImport() {}
function markedWithUnrelatedAttribute() {}
PHP
            ,
            $astLocator,
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace TheNamespace;

use NoDiscard as ImportedNoDiscard;

function untouched() {}
#[\NoDiscard]
function becameNoDiscard() {}
function noLongerNoDiscard() {}
#[\NoDiscard]
function stillNoDiscard() {}
#[\nodiscard]
function becameNoDiscardWithDifferentCasing() {}
#[ImportedNoDiscard]
function becameNoDiscardViaImport() {}
#[NoDiscard]
function markedWithUnrelatedAttribute() {}
PHP
            ,
            $astLocator,
        );

        $fromReflector = new DefaultReflector($fromLocator);
        $toReflector   = new DefaultReflector($toLocator);

        $functions = [
            'untouched'                          => [],
            'becameNoDiscard'                    => ['[BC] CHANGED: TheNamespace\becameNoDiscard() was marked "#[\NoDiscard]"'],
            'noLongerNoDiscard'                  => [],
            'stillNoDiscard'                     => [],
            'becameNoDiscardWithDifferentCasing' => ['[BC] CHANGED: TheNamespace\becameNoDiscardWithDifferentCasing() was marked "#[\NoDiscard]"'],
            'becameNoDiscardViaImport'           => ['[BC] CHANGED: TheNamespace\becameNoDiscardViaImport() was marked "#[\NoDiscard]"'],
            'markedWithUnrelatedAttribute'       => [],
        ];

        return array_combine(
            array_keys($functions),
            array_map(
                static fn (string $function, array $errors): array => [
                    $fromReflector->reflectFunction('TheNamespace\\' . $function),
                    $toReflector->reflectFunction('TheNamespace\\' . $function),
                    $errors,
                ],
                array_keys($functions),
                $functions,
            ),
        );
    }
}
