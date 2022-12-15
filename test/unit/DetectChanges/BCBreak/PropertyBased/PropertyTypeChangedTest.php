<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyTypeChanged;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsContravariant;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsCovariant;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use RoaveTest\BackwardCompatibility\TypeRestriction;

use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

/** @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyTypeChanged */
final class PropertyTypeChangedTest extends TestCase
{
    /**
     * @param string[] $expectedMessages
     *
     * @dataProvider propertiesToBeTested
     */
    public function testDiffs(
        ReflectionProperty $fromProperty,
        ReflectionProperty $toProperty,
        array $expectedMessages,
    ): void {
        $changes = (new PropertyTypeChanged(
            new TypeIsContravariant(),
            new TypeIsCovariant(),
        ))($fromProperty, $toProperty);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes)),
        );
    }

    /**
     * @return array<string, array<int, ReflectionProperty|array<int, string>>>
     * @psalm-return array<string, array{0: ReflectionProperty, 1: ReflectionProperty, 2: list<string>}>
     */
    public function propertiesToBeTested(): array
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
    
    /**
     * @var int
     */
    private $privateDocblockToDifferentDocblock;
    
    /**
     * @var int|int
     */
    private $duplicatePropertyTypesBeingDeduplicatedAreNotBcBreaks;
    
    /**
     * @var int
     */
    private $propertyTypeBeingDuplicatedAreNotBcBreaks;

    /**
     * @var GenericType<T1, T2>
     */ 
    public $propertyWithComplexDocblockThatCannotBeParsed;
    
    /**
     * @var int
     */
    public $propertyWithDocblockTypeHintChangeToNativeTypeHint;
    
    /**
     * @var int
     */
    public $propertyWithDocblockTypeHintChangeToNativeTypeHintAndTypeChange;

    public int $propertyWithDeclaredTypeRemoved;
}
PHP
            ,
            $astLocator,
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
    
    /**
     * @var float
     */
    private $privateDocblockToDifferentDocblock;
    
    /**
     * @var int
     */
    private $duplicatePropertyTypesBeingDeduplicatedAreNotBcBreaks;
    
    /**
     * @var int|int
     */
    private $propertyTypeBeingDuplicatedAreNotBcBreaks;

    /**
     * @var GenericType<T1, T2>
     */ 
    public $propertyWithComplexDocblockThatCannotBeParsed;
    
    public int $propertyWithDocblockTypeHintChangeToNativeTypeHint;
 
    public float $propertyWithDocblockTypeHintChangeToNativeTypeHintAndTypeChange;

    public $propertyWithDeclaredTypeRemoved;
}
PHP
            ,
            $astLocator,
        );

        $fromClassReflector = new DefaultReflector($fromLocator);
        $toClassReflector   = new DefaultReflector($toLocator);
        $fromClass          = $fromClassReflector->reflectClass('TheClass');
        $toClass            = $toClassReflector->reflectClass('TheClass');

        // Note: lots of docblock-related tests report no BC breaks here. This is because this change checker does
        //       not operate on documented types anymore. Documented types are too advanced for this library to inspect,
        //       right now, since psalm/phpstan/psr-5 are constantly evolving. The library will limit itself in
        //       inspecting reflection-based type changes (for now).
        $properties = [
            'publicNoDocblockToNoDocblock'                                      => [],
            'publicNoDocblockToDocblock'                                        => [],
            'publicNoTypeDocblockToDocblock'                                    => [],
            'publicDocblockToSameDocblock'                                      => [],
            'publicDocblockToDifferentDocblock'                                 => [],
            'publicDocblockToNoDocblock'                                        => [],
            'publicCompositeTypeDocblockToSameTypeDocblock'                     => [],
            'publicCompositeTypeDocblockToSameTypeDocblockWithDifferentSorting' => [],
            'publicCompositeTypeDocblockToDifferentCompositeTypeDocblock'       => [],
            'privateDocblockToDifferentDocblock'                                => [],
            'duplicatePropertyTypesBeingDeduplicatedAreNotBcBreaks'             => [],
            'propertyTypeBeingDuplicatedAreNotBcBreaks'                         => [],
            'propertyWithComplexDocblockThatCannotBeParsed'                     => [],
            'propertyWithDocblockTypeHintChangeToNativeTypeHint'                => ['[BC] CHANGED: Type of property TheClass#$propertyWithDocblockTypeHintChangeToNativeTypeHint changed from having no type to int'],
            'propertyWithDocblockTypeHintChangeToNativeTypeHintAndTypeChange'   => ['[BC] CHANGED: Type of property TheClass#$propertyWithDocblockTypeHintChangeToNativeTypeHintAndTypeChange changed from having no type to float'],
            'propertyWithDeclaredTypeRemoved'                                   => ['[BC] CHANGED: Type of property TheClass#$propertyWithDeclaredTypeRemoved changed from int to having no type'],
        ];

        return array_combine(
            array_keys($properties),
            array_map(
                static fn (string $property, array $errorMessages): array => [
                    TypeRestriction::object($fromClass->getProperty($property)),
                    TypeRestriction::object($toClass->getProperty($property)),
                    $errorMessages,
                ],
                array_keys($properties),
                $properties,
            ),
        );
    }
}
