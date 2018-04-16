<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased\PropertyDocumentedTypeChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\ApiCompare\Comparator\BackwardsCompatibility\PropertyBased\PropertyDocumentedTypeChanged
 */
final class PropertyDocumentedTypeChangedTest extends TestCase
{
    /**
     * @dataProvider propertiesToBeTested
     *
     * @param string[] $expectedMessages
     */
    public function testDiffs(
        ReflectionProperty $fromProperty,
        ReflectionProperty $toProperty,
        array $expectedMessages
    ) : void {
        $changes = (new PropertyDocumentedTypeChanged())
            ->compare($fromProperty, $toProperty);

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
    public $publicNoDocblockToNoDocblock;
    public $publicNoDocblockToDocblock;
    
    /**
     * Hi
     */
    public $publicNoTypeDocblockToDocblock;
    
    /**
     * @var int
     */
    public $publicDocblockToSameDocblock;
    
    /**
     * @var int
     */
    public $publicDocblockToDifferentDocblock;
    
    /**
     * @var int
     */
    public $publicDocblockToNoDocblock;
    
    /**
     * @var int|float
     */
    public $publicCompositeTypeDocblockToSameTypeDocblock;
    
    /**
     * @var int|float
     */
    public $publicCompositeTypeDocblockToSameTypeDocblockWithDifferentSorting;
    
    /**
     * @var int|float
     */
    public $publicCompositeTypeDocblockToDifferentCompositeTypeDocblock;
}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public $publicNoDocblockToNoDocblock;
    
    /**
     * @var int
     */
    public $publicNoDocblockToDocblock;
    
    /**
     * @var int
     */
    public $publicNoTypeDocblockToDocblock;
    
    /**
     * @var int
     */
    public $publicDocblockToSameDocblock;
    
    /**
     * @var float
     */
    public $publicDocblockToDifferentDocblock;
    
    public $publicDocblockToNoDocblock;
    
    /**
     * @var int|float
     */
    public $publicCompositeTypeDocblockToSameTypeDocblock;
    
    /**
     * @var float|int
     */
    public $publicCompositeTypeDocblockToSameTypeDocblockWithDifferentSorting;
    
    /**
     * @var int|float|string
     */
    public $publicCompositeTypeDocblockToDifferentCompositeTypeDocblock;
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
            'publicNoDocblockToNoDocblock'                                      => [],
            'publicNoDocblockToDocblock'                                        => [],
            'publicNoTypeDocblockToDocblock'                                        => [
                '[BC] CHANGED: Type documentation for property TheClass::$publicNoTypeDocblockToDocblock changed from having no type to int',
            ],
            'publicDocblockToSameDocblock'                                      => [],
            'publicDocblockToDifferentDocblock'                                 => [
                '[BC] CHANGED: Type documentation for property TheClass::$publicDocblockToDifferentDocblock changed from int to float',
            ],
            'publicDocblockToNoDocblock' => [
                '[BC] CHANGED: Type documentation for property TheClass::$publicDocblockToNoDocblock changed from int to having no type',
            ],
            'publicCompositeTypeDocblockToSameTypeDocblock'                     => [],
            'publicCompositeTypeDocblockToSameTypeDocblockWithDifferentSorting' => [],
            'publicCompositeTypeDocblockToDifferentCompositeTypeDocblock'       => [
                '[BC] CHANGED: Type documentation for property TheClass::$publicCompositeTypeDocblockToDifferentCompositeTypeDocblock changed from float|int to float|int|string',
            ],
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
