<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psl\Type;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassConstantBased\ClassConstantBecameFinal;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

#[CoversClass(ClassConstantBecameFinal::class)]
final class ClassConstantBecameFinalTest extends TestCase
{
    /** @param string[] $expectedMessages */
    #[DataProvider('constantsToBeTested')]
    public function testDiffs(
        ReflectionClassConstant $fromConstant,
        ReflectionClassConstant $toConstant,
        array $expectedMessages,
    ): void {
        $changes = (new ClassConstantBecameFinal())($fromConstant, $toConstant);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes)),
        );
    }

    /**
     * @return array<string, array{
     *     0: ReflectionClassConstant,
     *     1: ReflectionClassConstant,
     *     2: list<string>
     * }>
     */
    public static function constantsToBeTested(): array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public const publicOverrideableToFinal = 'value';
    final public const publicFinalToOverrideable = 'value';
    public const publicOverrideableToOverrideable = 'value';
    final public const publicFinalToFinal = 'value';

    protected const protectedOverrideableToFinal = 'value';
    final protected const protectedFinalToOverrideable = 'value';
    protected const protectedOverrideableToOverrideable = 'value';
    final protected const protectedFinalToFinal = 'value';
}
PHP
            ,
            $astLocator,
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    final public const publicOverrideableToFinal = 'value';
    public const publicFinalToOverrideable = 'value';
    public const publicOverrideableToOverrideable = 'value';
    final public const publicFinalToFinal = 'value';

    final protected const protectedOverrideableToFinal = 'value';
    protected const protectedFinalToOverrideable = 'value';
    protected const protectedOverrideableToOverrideable = 'value';
    final protected const protectedFinalToFinal = 'value';
}
PHP
            ,
            $astLocator,
        );

        $fromClassReflector = new DefaultReflector($fromLocator);
        $toClassReflector   = new DefaultReflector($toLocator);
        $fromClass          = $fromClassReflector->reflectClass('TheClass');
        $toClass            = $toClassReflector->reflectClass('TheClass');

        $constants = [
            'publicOverrideableToFinal' => ['[BC] CHANGED: Constant TheClass::publicOverrideableToFinal became final'],
            'publicFinalToOverrideable' => [],
            'publicOverrideableToOverrideable' => [],
            'publicFinalToFinal' => [],
            'protectedOverrideableToFinal' => ['[BC] CHANGED: Constant TheClass::protectedOverrideableToFinal became final'],
            'protectedFinalToOverrideable' => [],
            'protectedOverrideableToOverrideable' => [],
            'protectedFinalToFinal' => [],
        ];

        return array_combine(
            array_keys($constants),
            array_map(
                static fn (string $constant, array $errorMessages): array => [
                    Type\instance_of(ReflectionClassConstant::class)
                        ->coerce($fromClass->getConstant($constant)),
                    Type\instance_of(ReflectionClassConstant::class)
                        ->coerce($toClass->getConstant($constant)),
                    $errorMessages,
                ],
                array_keys($constants),
                $constants,
            ),
        );
    }
}
