<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased\PropertyDefaultValueChanged;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased\PropertyScopeChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased\PropertyScopeChanged
 */
final class PropertyScopeChangedTest extends TestCase
{
    /**
     * @dataProvider propertiesToBeTested
     *
     * @param string[] $expectedMessages
     */
    public function testDiffs(
        ReflectionProperty $fromFunction,
        ReflectionProperty $toFunction,
        array $expectedMessages
    ) : void {
        $changes = (new PropertyScopeChanged())
            ->compare($fromFunction, $toFunction);

        self::assertSame(
            $expectedMessages,
            array_map(function (Change $change) : string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /** @return (string[]|ReflectionProperty)[][] */
    public function propertiesToBeTested() : array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public $publicInstanceToStatic;
    public static $publicStaticToInstance;
    public $publicInstanceToInstance;
    public static $publicStaticToStatic;
    
    protected $protectedInstanceToStatic;
    protected static $protectedStaticToInstance;
    protected $protectedInstanceToInstance;
    protected static $protectedStaticToStatic;
    
    private $privateInstanceToStatic;
    private static $privateStaticToInstance;
    private $privateInstanceToInstance;
    private static $privateStaticToStatic;
}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public static $publicInstanceToStatic;
    public $publicStaticToInstance;
    public $publicInstanceToInstance;
    public static $publicStaticToStatic;
    
    protected static $protectedInstanceToStatic;
    protected $protectedStaticToInstance;
    protected $protectedInstanceToInstance;
    protected static $protectedStaticToStatic;
    
    private static $privateInstanceToStatic;
    private $privateStaticToInstance;
    private $privateInstanceToInstance;
    private static $privateStaticToStatic;
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
            'publicInstanceToStatic'   => [
                '[BC] CHANGED: Property $publicInstanceToStatic of TheClass changed scope from instance to static',
            ],
            'publicStaticToInstance'   => [
                '[BC] CHANGED: Property $publicStaticToInstance of TheClass changed scope from static to instance',
            ],
            'publicInstanceToInstance' => [],
            'publicStaticToStatic'     => [],

            'protectedInstanceToStatic'    => [
                '[BC] CHANGED: Property $protectedInstanceToStatic of TheClass changed scope from instance to static',
            ],
            'protectedStaticToInstance'    => [
                '[BC] CHANGED: Property $protectedStaticToInstance of TheClass changed scope from static to instance',
            ],
            'protectedInstanceToInstance' => [],
            'protectedStaticToStatic'      => [],

            'privateInstanceToStatic'    => [],
            'privateStaticToInstance'   => [],
            'privateInstanceToInstance' => [],
            'privateStaticToStatic'     => [],
        ];

        return array_combine(
            array_keys($properties),
            array_map(
                function (string $property, array $errorMessages) use ($fromClass, $toClass) : array {
                    return [
                        $fromClass->getProperty($property),
                        $toClass->getProperty($property),
                        $errorMessages,
                    ];
                },
                array_keys($properties),
                $properties
            )
        );
    }
}
