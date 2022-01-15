<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;

use PHPUnit\Framework\TestCase;
use Psl\Type;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\ClassConstantValueChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
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
     * @param string[] $expectedMessages
     *
     * @dataProvider constantsToBeTested
     */
    public function testDiffs(
        ReflectionClassConstant $fromConstant,
        ReflectionClassConstant $toConstant,
        array $expectedMessages
    ): void {
        $changes = (new ClassConstantValueChanged())($fromConstant, $toConstant);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /**
     * @return array<string, array{
     *     0: ReflectionClassConstant,
     *     1: ReflectionClassConstant,
     *     2: list<string>
     * }>
     */
    public function constantsToBeTested(): array
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

        $fromClassReflector = new DefaultReflector($fromLocator);
        $toClassReflector   = new DefaultReflector($toLocator);
        $fromClass          = $fromClassReflector->reflectClass('TheClass');
        $toClass            = $toClassReflector->reflectClass('TheClass');

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
                static fn (string $constant, array $errorMessages): array => [
                    Type\object(ReflectionClassConstant::class)
                        ->coerce($fromClass->getReflectionConstant($constant)),
                    Type\object(ReflectionClassConstant::class)
                        ->coerce($toClass->getReflectionConstant($constant)),
                    $errorMessages,
                ],
                array_keys($properties),
                $properties
            )
        );
    }

    /**
     * @return list<array{0:string, 1:list<string>}>
     */
    public function classConstantsWithMagicConstantsProvider(): array
    {
        return [
            ['valueWithDirDoesNotChange', []],
            ['valueWithDirDoesChange', ['[BC] CHANGED: Value of constant ClassWithDirConstants::valueWithDirDoesChange changed from \'__DIR_HAS_BEEN_REPLACED__/foo\' to \'__DIR_HAS_BEEN_REPLACED__/bar\'']],
            ['valueWithFileDoesNotChange', []],
            ['valueWithFileDoesChange', ['[BC] CHANGED: Value of constant ClassWithDirConstants::valueWithFileDoesChange changed from \'__DIR_HAS_BEEN_REPLACED__/ClassWithDirConstants.php/foo\' to \'__DIR_HAS_BEEN_REPLACED__/ClassWithDirConstants.php/bar\'']],
        ];
    }

    /**
     * @param list<string> $expectedMessages
     *
     * @dataProvider classConstantsWithMagicConstantsProvider
     */
    public function testConstantsWithDir(string $constantName, array $expectedMessages): void
    {
        $astLocator = (new BetterReflection())->astLocator();

        // Must be a real file in this test, otherwise __DIR__ cannot be compiled by Better Reflection
        $fromLocator = new SingleFileSourceLocator(__DIR__ . '/../../../../asset/api/old/ClassWithDirConstants.php', $astLocator);
        $toLocator   = new SingleFileSourceLocator(__DIR__ . '/../../../../asset/api/new/ClassWithDirConstants.php', $astLocator);

        $fromClassReflector = new DefaultReflector($fromLocator);
        $toClassReflector   = new DefaultReflector($toLocator);
        $fromClass          = $fromClassReflector->reflectClass('ClassWithDirConstants');
        $toClass            = $toClassReflector->reflectClass('ClassWithDirConstants');

        $fromConstant = Type\object(ReflectionClassConstant::class)
            ->coerce($fromClass->getReflectionConstant($constantName));
        $toConstant   = Type\object(ReflectionClassConstant::class)
            ->coerce($toClass->getReflectionConstant($constantName));
        $changes      = (new ClassConstantValueChanged())($fromConstant, $toConstant);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }
}
