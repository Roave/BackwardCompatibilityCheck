<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodBecameFinal;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_keys;
use function array_map;
use function iterator_to_array;
use function Safe\array_combine;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodBecameFinal
 */
final class MethodBecameFinalTest extends TestCase
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
    ) : void {
        $changes = (new MethodBecameFinal())
            ->__invoke($fromMethod, $toMethod);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change) : string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /** @return string[][][]|ReflectionProperty[][][] */
    public function propertiesToBeTested() : array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

abstract class TheClass {
    public function publicOverrideableToFinal() {}
    public final function publicFinalToOverrideable() {}
    public function publicOverrideableToOverrideable() {}
    public final function publicFinalToFinal() {}
    
    protected function protectedOverrideableToFinal() {}
    protected final function protectedFinalToOverrideable() {}
    protected function protectedOverrideableToOverrideable() {}
    protected final function protectedFinalToFinal() {}
    
    private function privateOverrideableToFinal() {}
    private final function privateFinalToOverrideable() {}
    private function privateOverrideableToOverrideable() {}
    private final function privateFinalToFinal() {}
}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

abstract class TheClass {
    public final function publicOverrideableToFinal() {}
    public function publicFinalToOverrideable() {}
    public function publicOverrideableToOverrideable() {}
    public final function publicFinalToFinal() {}
    
    protected final function protectedOverrideableToFinal() {}
    protected function protectedFinalToOverrideable() {}
    protected function protectedOverrideableToOverrideable() {}
    protected final function protectedFinalToFinal() {}
    
    private final function privateOverrideableToFinal() {}
    private function privateFinalToOverrideable() {}
    private function privateOverrideableToOverrideable() {}
    private final function privateFinalToFinal() {}
}
PHP
            ,
            $astLocator
        );

        $fromClassReflector = new ClassReflector($fromLocator);
        $toClassReflector   = new ClassReflector($toLocator);
        $fromClass          = $fromClassReflector->reflect('TheClass');
        $toClass            = $toClassReflector->reflect('TheClass');

        $properties = [
            'publicOverrideableToFinal' => ['[BC] CHANGED: Method publicOverrideableToFinal() of class TheClass became final'],
            'publicFinalToOverrideable' => [],
            'publicOverrideableToOverrideable' => [],
            'publicFinalToFinal' => [],

            'protectedOverrideableToFinal' => ['[BC] CHANGED: Method protectedOverrideableToFinal() of class TheClass became final'],
            'protectedFinalToOverrideable' => [],
            'protectedOverrideableToOverrideable' => [],
            'protectedFinalToFinal' => [],

            'privateOverrideableToFinal' => ['[BC] CHANGED: Method privateOverrideableToFinal() of class TheClass became final'],
            'privateFinalToOverrideable' => [],
            'privateOverrideableToOverrideable' => [],
            'privateFinalToFinal' => [],
        ];

        return array_combine(
            array_keys($properties),
            array_map(
                static function (string $methodName, array $errorMessages) use ($fromClass, $toClass) : array {
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
