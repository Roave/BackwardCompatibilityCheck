<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\MethodScopeChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_map;
use function iterator_to_array;

final class MethodScopeChangedTest extends TestCase
{
    /**
     * @dataProvider classesToBeTested
     *
     * @param string[] $expectedMessages
     */
    public function testDiffs(
        ReflectionClass $fromClass,
        ReflectionClass $toClass,
        array $expectedMessages
    ) : void {
        $changes = (new MethodScopeChanged())
            ->compare($fromClass, $toClass);

        self::assertSame(
            $expectedMessages,
            array_map(function (Change $change) : string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /** @return (string[]|ReflectionClass)[][] */
    public function classesToBeTested() : array
    {
        $locator       = (new BetterReflection())->astLocator();
        $fromReflector = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

class A { 
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
    
    public static function publicStaticToPrivateInstance() {}
}
PHP
            ,
            $locator
        ));
        $toReflector   = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

class A { 
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
    
    private function publicStaticToPrivateInstance() {}
}
PHP
            ,
            $locator
        ));

        return [
            'A' => [
                $fromReflector->reflect('A'),
                $toReflector->reflect('A'),
                [
                    '[BC] CHANGED: Method publicInstanceToStatic() of class A changed scope from instance to static',
                    '[BC] CHANGED: Method publicStaticToInstance() of class A changed scope from static to instance',
                    '[BC] CHANGED: Method protectedInstanceToStatic() of class A changed scope from instance to static',
                    '[BC] CHANGED: Method protectedStaticToInstance() of class A changed scope from static to instance',
                ],
            ],
        ];
    }
}
