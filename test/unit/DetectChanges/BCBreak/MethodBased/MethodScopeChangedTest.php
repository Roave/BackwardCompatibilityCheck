<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodScopeChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodScopeChanged
 */
final class MethodScopeChangedTest extends TestCase
{
    /**
     * @param string[] $expectedMessages
     *
     * @dataProvider propertiesToBeTested
     */
    public function testDiffs(
        ReflectionMethod $fromMethod,
        ReflectionMethod $toMethod,
        array $expectedMessages
    ): void {
        $changes = (new MethodScopeChanged())($fromMethod, $toMethod);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /**
     * @return array<string, array<int, ReflectionMethod|array<int, string>>>
     * @psalm-return array<string, array{0: ReflectionMethod, 1: ReflectionMethod, 2: list<string>}>
     */
    public function propertiesToBeTested(): array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public function publicInstanceToStatic() {}
    public static function publicStaticToInstance() {}
    public function publicInstanceToInstance() {}
    public static function publicStaticToStatic() {}
    
    protected function protectedInstanceToStatic() {}
    protected static function protectedStaticToInstance() {}
    protected function protectedInstanceToInstance() {}
    protected static function protectedStaticToStatic() {}
    
    private function privateInstanceToStatic() {}
    private static function privateStaticToInstance() {}
    private function privateInstanceToInstance() {}
    private static function privateStaticToStatic() {}
}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public static function publicInstanceToStatic() {}
    public function publicStaticToInstance() {}
    public function publicInstanceToInstance() {}
    public static function publicStaticToStatic() {}
    
    protected static function protectedInstanceToStatic() {}
    protected function protectedStaticToInstance() {}
    protected function protectedInstanceToInstance() {}
    protected static function protectedStaticToStatic() {}
    
    private static function privateInstanceToStatic() {}
    private function privateStaticToInstance() {}
    private function privateInstanceToInstance() {}
    private static function privateStaticToStatic() {}
}
PHP
            ,
            $astLocator
        );

        $fromClassReflector = new DefaultReflector($fromLocator);
        $toClassReflector   = new DefaultReflector($toLocator);
        $fromClass          = $fromClassReflector->reflectClass('TheClass');
        $toClass            = $toClassReflector->reflectClass('TheClass');

        $properties = [
            'publicInstanceToStatic'   => ['[BC] CHANGED: Method publicInstanceToStatic() of class TheClass changed scope from instance to static'],
            'publicStaticToInstance'   => ['[BC] CHANGED: Method publicStaticToInstance() of class TheClass changed scope from static to instance'],
            'publicInstanceToInstance' => [],
            'publicStaticToStatic'     => [],

            'protectedInstanceToStatic'   => ['[BC] CHANGED: Method protectedInstanceToStatic() of class TheClass changed scope from instance to static'],
            'protectedStaticToInstance'   => ['[BC] CHANGED: Method protectedStaticToInstance() of class TheClass changed scope from static to instance'],
            'protectedInstanceToInstance' => [],
            'protectedStaticToStatic'     => [],

            'privateInstanceToStatic'   => ['[BC] CHANGED: Method privateInstanceToStatic() of class TheClass changed scope from instance to static'],
            'privateStaticToInstance'   => ['[BC] CHANGED: Method privateStaticToInstance() of class TheClass changed scope from static to instance'],
            'privateInstanceToInstance' => [],
            'privateStaticToStatic'     => [],
        ];

        return array_combine(
            array_keys($properties),
            array_map(
                /** @psalm-param list<string> $errorMessages https://github.com/vimeo/psalm/issues/2772 */
                static function (string $methodName, array $errorMessages) use ($fromClass, $toClass): array {
                    return [
                        $fromClass->getMethod($methodName),
                        $toClass->getMethod($methodName),
                        $errorMessages,
                    ];
                },
                array_keys($properties),
                $properties
            )
        );
    }
}
