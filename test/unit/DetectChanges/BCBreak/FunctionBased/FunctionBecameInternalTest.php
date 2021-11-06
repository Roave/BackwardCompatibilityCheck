<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\FunctionBecameInternal;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

/** @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\FunctionBecameInternal */
final class FunctionBecameInternalTest extends TestCase
{
    /**
     * @param string[] $expectedMessages
     *
     * @dataProvider functionsToBeTested
     */
    public function testDiffs(
        ReflectionFunctionAbstract $fromFunction,
        ReflectionFunctionAbstract $toFunction,
        array $expectedMessages
    ): void {
        $changes = (new FunctionBecameInternal())($fromFunction, $toFunction);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /**
     * @return array<string, array<int, ReflectionFunctionAbstract|array<int, string>>>
     * @psalm-return array<string, array{0: ReflectionFunctionAbstract, 1: ReflectionFunctionAbstract, 2: list<string>}>
     */
    public function functionsToBeTested(): array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

function a() {}
function b() {}
/** @internal */
function c() {}
/** @internal */
function d() {}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

function a() {}
/** @internal */
function b() {}
function c() {}
/** @internal */
function d() {}
PHP
            ,
            $astLocator
        );

        $fromReflector = new DefaultReflector($fromLocator);
        $toReflector   = new DefaultReflector($toLocator);

        $functions = [
            'a' => [],
            'b' => ['[BC] CHANGED: b() was marked "@internal"'],
            'c' => [],
            'd' => [],
        ];

        return array_combine(
            array_keys($functions),
            array_map(
                /** @psalm-param list<string> $errorMessages https://github.com/vimeo/psalm/issues/2772 */
                static function (string $function, array $errorMessages) use ($fromReflector, $toReflector): array {
                    return [
                        $fromReflector->reflectFunction($function),
                        $toReflector->reflectFunction($function),
                        $errorMessages,
                    ];
                },
                array_keys($functions),
                $functions
            )
        );
    }
}
