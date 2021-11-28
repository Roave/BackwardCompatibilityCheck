<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyDocumentedTypeChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use RoaveTest\BackwardCompatibility\TypeRestriction;

use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyDocumentedTypeChanged
 */
final class PropertyDocumentedTypeChangedTest extends TestCase
{
    /**
     * @param string[] $expectedMessages
     *
     * @dataProvider propertiesToBeTested
     */
    public function testDiffs(
        ReflectionProperty $fromProperty,
        ReflectionProperty $toProperty,
        array $expectedMessages
    ): void {
        $changes = (new PropertyDocumentedTypeChanged())($fromProperty, $toProperty);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes))
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
    
    /**
     * @var RoaveTest\BackwardCompatibility\Stubs\DemoStub
     */
    public $propertyWithDocblockTypeHintChangeToNativeWithShortNamespaceClass;
}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

use RoaveTest\BackwardCompatibility\Stubs\DemoStub;

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
            
    public DemoStub $propertyWithDocblockTypeHintChangeToNativeWithShortNamespaceClass;
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
            'publicNoTypeDocblockToDocblock'                                    => ['[BC] CHANGED: Type documentation for property TheClass#$publicNoTypeDocblockToDocblock changed from having no type to int'],
            'publicDocblockToSameDocblock'                                      => [],
            'publicDocblockToDifferentDocblock'                                 => ['[BC] CHANGED: Type documentation for property TheClass#$publicDocblockToDifferentDocblock changed from int to float'],
            'publicDocblockToNoDocblock'                                        => ['[BC] CHANGED: Type documentation for property TheClass#$publicDocblockToNoDocblock changed from int to having no type'],
            'publicCompositeTypeDocblockToSameTypeDocblock'                     => [],
            'publicCompositeTypeDocblockToSameTypeDocblockWithDifferentSorting' => [],
            'publicCompositeTypeDocblockToDifferentCompositeTypeDocblock'       => ['[BC] CHANGED: Type documentation for property TheClass#$publicCompositeTypeDocblockToDifferentCompositeTypeDocblock changed from float|int to float|int|string'],
            'privateDocblockToDifferentDocblock'                                => ['[BC] CHANGED: Type documentation for property TheClass#$privateDocblockToDifferentDocblock changed from int to float'],
            'duplicatePropertyTypesBeingDeduplicatedAreNotBcBreaks'             => [],
            'propertyTypeBeingDuplicatedAreNotBcBreaks'                         => [],
            'propertyWithComplexDocblockThatCannotBeParsed'                     => [],
            'propertyWithDocblockTypeHintChangeToNativeTypeHint'                => [],
            'propertyWithDocblockTypeHintChangeToNativeTypeHintAndTypeChange'   => ['[BC] CHANGED: Type documentation for property TheClass#$propertyWithDocblockTypeHintChangeToNativeTypeHintAndTypeChange changed from int to float'],
            'propertyWithDocblockTypeHintChangeToNativeWithShortNamespaceClass' => [],
        ];

        return array_combine(
            array_keys($properties),
            array_map(
                /** @psalm-param list<string> $errorMessages https://github.com/vimeo/psalm/issues/2772 */
                static function (string $property, array $errorMessages) use ($fromClass, $toClass): array {
                    return [
                        TypeRestriction::object($fromClass->getProperty($property)),
                        TypeRestriction::object($toClass->getProperty($property)),
                        $errorMessages,
                    ];
                },
                array_keys($properties),
                $properties
            )
        );
    }
}
