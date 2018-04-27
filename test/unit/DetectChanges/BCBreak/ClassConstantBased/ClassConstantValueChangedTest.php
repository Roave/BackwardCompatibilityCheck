<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\ClassConstantValueChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\ClassConstantValueChanged
 */
final class ClassConstantValueChangedTest extends TestCase
{
    /**
     * @dataProvider propertiesToBeTested
     *
     * @param string[] $expectedMessages
     */
    public function testDiffs(
        ReflectionClassConstant $fromConstant,
        ReflectionClassConstant $toConstant,
        array $expectedMessages
    ) : void {
        $changes = (new ClassConstantValueChanged())
            ->__invoke($fromConstant, $toConstant);

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
    public const publicNullToNull = null;
    public const publicValueChanged = 1;
    public const publicValueToSimilarValue = '1';
    public const publicExpressionToExpressionValue = 101 + 5;
    
    protected const protectedNullToNull = null;
    protected const protectedValueChanged = 1;
    protected const protectedValueToSimilarValue = '1';
    protected const protectedExpressionToExpressionValue = 101 + 5;
    
    private const privateNullToNull = null;
    private const privateValueChanged = 1;
    private const privateValueToSimilarValue = '1';
    private const privateExpressionToExpressionValue = 101 + 5;
}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public const publicNullToNull = null;
    public const publicValueChanged = 2;
    public const publicValueToSimilarValue = 1;
    public const publicExpressionToExpressionValue = 106;
    
    protected const protectedNullToNull = null;
    protected const protectedValueChanged = 2;
    protected const protectedValueToSimilarValue = 1;
    protected const protectedExpressionToExpressionValue = 106;
    
    private const privateNullToNull = null;
    private const privateValueChanged = 2;
    private const privateValueToSimilarValue = 1;
    private const privateExpressionToExpressionValue = 106;
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
            'publicNullToNull'                  => [],
            'publicValueChanged'                => ['[BC] CHANGED: Value of constant TheClass::publicValueChanged changed from 1 to 2'],
            'publicValueToSimilarValue'         => ['[BC] CHANGED: Value of constant TheClass::publicValueToSimilarValue changed from \'1\' to 1'],
            'publicExpressionToExpressionValue' => [],
            'protectedNullToNull'                  => [],
            'protectedValueChanged'                => ['[BC] CHANGED: Value of constant TheClass::protectedValueChanged changed from 1 to 2'],
            'protectedValueToSimilarValue'         => ['[BC] CHANGED: Value of constant TheClass::protectedValueToSimilarValue changed from \'1\' to 1'],
            'protectedExpressionToExpressionValue' => [],
            'privateNullToNull'                  => [],
            'privateValueChanged'                => [],
            'privateValueToSimilarValue'         => [],
            'privateExpressionToExpressionValue' => [],
        ];

        return array_combine(
            array_keys($properties),
            array_map(
                function (string $constant, array $errorMessages) use ($fromClass, $toClass) : array {
                    return [
                        $fromClass->getReflectionConstant($constant),
                        $toClass->getReflectionConstant($constant),
                        $errorMessages,
                    ];
                },
                array_keys($properties),
                $properties
            )
        );
    }
}
