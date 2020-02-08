<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyDefaultValueChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use RoaveTest\BackwardCompatibility\TypeRestriction;
use function array_keys;
use function array_map;
use function iterator_to_array;
use function array_combine;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyDefaultValueChanged
 */
final class PropertyDefaultValueChangedTest extends TestCase
{
    /**
     * @param string[] $expectedMessages
     *
     * @dataProvider propertiesToBeTested
     */
    public function testDiffs(
        ReflectionProperty $fromFunction,
        ReflectionProperty $toFunction,
        array $expectedMessages
    ) : void {
        $changes = (new PropertyDefaultValueChanged())
            ->__invoke($fromFunction, $toFunction);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change) : string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /**
     * @return array<string, array<int, ReflectionProperty|array<int, string>>>
     *
     * @psalm-return array<string, array{0: ReflectionProperty, 1: ReflectionProperty, 2: list<string>}>
     */
    public function propertiesToBeTested() : array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public $publicNothingToNothing;
    public $publicNothingToNull;
    public $publicNullToNull = null;
    public $publicValueChanged = 1;
    public $publicValueToSimilarValue = '1';
    public $publicExpressionToExpressionValue = 101 + 5;
    
    protected $protectedNothingToNothing;
    protected $protectedNothingToNull;
    protected $protectedNullToNull = null;
    protected $protectedValueChanged = 1;
    protected $protectedValueToSimilarValue = '1';
    protected $protectedExpressionToExpressionValue = 101 + 5;
    
    private $privateNothingToNothing;
    private $privateNothingToNull;
    private $privateNullToNull = null;
    private $privateValueChanged = 1;
    private $privateValueToSimilarValue = '1';
    private $privateExpressionToExpressionValue = 101 + 5;
}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public $publicNothingToNothing;
    public $publicNothingToNull = null;
    public $publicNullToNull = null;
    public $publicValueChanged = 2;
    public $publicValueToSimilarValue = 1;
    public $publicExpressionToExpressionValue = 106;
    
    protected $protectedNothingToNothing;
    protected $protectedNothingToNull = null;
    protected $protectedNullToNull = null;
    protected $protectedValueChanged = 2;
    protected $protectedValueToSimilarValue = 1;
    protected $protectedExpressionToExpressionValue = 106;
    
    private $privateNothingToNothing;
    private $privateNothingToNull = null;
    private $privateNullToNull = null;
    private $privateValueChanged = 2;
    private $privateValueToSimilarValue = 1;
    private $privateExpressionToExpressionValue = 106;
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
            'publicNothingToNothing'               => [],
            'publicNothingToNull'                  => [],
            'publicNullToNull'                     => [],
            'publicValueChanged'                   => ['[BC] CHANGED: Property TheClass#$publicValueChanged changed default value from 1 to 2'],
            'publicValueToSimilarValue'            => ['[BC] CHANGED: Property TheClass#$publicValueToSimilarValue changed default value from \'1\' to 1'],
            'publicExpressionToExpressionValue'    => [],
            'protectedNothingToNothing'            => [],
            'protectedNothingToNull'               => [],
            'protectedNullToNull'                  => [],
            'protectedValueChanged'                => ['[BC] CHANGED: Property TheClass#$protectedValueChanged changed default value from 1 to 2'],
            'protectedValueToSimilarValue'         => ['[BC] CHANGED: Property TheClass#$protectedValueToSimilarValue changed default value from \'1\' to 1'],
            'protectedExpressionToExpressionValue' => [],
            'privateNothingToNothing'              => [],
            'privateNothingToNull'                 => [],
            'privateNullToNull'                    => [],
            'privateValueChanged'                  => ['[BC] CHANGED: Property TheClass#$privateValueChanged changed default value from 1 to 2'],
            'privateValueToSimilarValue'           => ['[BC] CHANGED: Property TheClass#$privateValueToSimilarValue changed default value from \'1\' to 1'],
            'privateExpressionToExpressionValue'   => [],
        ];

        return TypeRestriction::array(array_combine(
            array_keys($properties),
            array_map(
                /** @psalm-param list<string> $errorMessages https://github.com/vimeo/psalm/issues/2772 */
                static function (string $property, array $errorMessages) use ($fromClass, $toClass) : array {
                    return [
                        TypeRestriction::object($fromClass->getProperty($property)),
                        TypeRestriction::object($toClass->getProperty($property)),
                        $errorMessages,
                    ];
                },
                array_keys($properties),
                $properties
            )
        ));
    }
}
