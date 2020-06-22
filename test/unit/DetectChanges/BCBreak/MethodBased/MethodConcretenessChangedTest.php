<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodConcretenessChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use RoaveTest\BackwardCompatibility\TypeRestriction;

use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodConcretenessChanged
 */
final class MethodConcretenessChangedTest extends TestCase
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
        $changes = (new MethodConcretenessChanged())
            ->__invoke($fromMethod, $toMethod);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /**
     * @return array<string, array<int, ReflectionMethod|array<int, string>>>
     *
     * @psalm-return array<string, array{0: ReflectionMethod, 1: ReflectionMethod, 2: list<string>}>
     */
    public function propertiesToBeTested(): array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

abstract class TheClass {
    public function publicConcreteToAbstract() {}
    public abstract function publicAbstractToConcrete() {}
    public function publicConcreteToConcrete() {}
    public abstract function publicAbstractToAbstract() {}
    
    protected function protectedConcreteToAbstract() {}
    protected abstract function protectedAbstractToConcrete() {}
    protected function protectedConcreteToConcrete() {}
    protected abstract function protectedAbstractToAbstract() {}
    
    private function privateConcreteToAbstract() {}
    private abstract function privateAbstractToConcrete() {}
    private function privateConcreteToConcrete() {}
    private abstract function privateAbstractToAbstract() {}
}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

abstract class TheClass {
    public abstract function publicConcreteToAbstract() {}
    public function publicAbstractToConcrete() {}
    public function publicConcreteToConcrete() {}
    public abstract function publicAbstractToAbstract() {}
    
    protected abstract function protectedConcreteToAbstract() {}
    protected function protectedAbstractToConcrete() {}
    protected function protectedConcreteToConcrete() {}
    protected abstract function protectedAbstractToAbstract() {}
    
    private abstract function privateConcreteToAbstract() {}
    private function privateAbstractToConcrete() {}
    private function privateConcreteToConcrete() {}
    private abstract function privateAbstractToAbstract() {}
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
            'publicConcreteToAbstract' => ['[BC] CHANGED: Method publicConcreteToAbstract() of class TheClass changed from concrete to abstract'],
            'publicAbstractToConcrete' => [],
            'publicConcreteToConcrete' => [],
            'publicAbstractToAbstract' => [],

            'protectedConcreteToAbstract' => ['[BC] CHANGED: Method protectedConcreteToAbstract() of class TheClass changed from concrete to abstract'],
            'protectedAbstractToConcrete' => [],
            'protectedConcreteToConcrete' => [],
            'protectedAbstractToAbstract' => [],

            'privateConcreteToAbstract' => ['[BC] CHANGED: Method privateConcreteToAbstract() of class TheClass changed from concrete to abstract'],
            'privateAbstractToConcrete' => [],
            'privateConcreteToConcrete' => [],
            'privateAbstractToAbstract' => [],
        ];

        return TypeRestriction::array(array_combine(
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
        ));
    }
}
