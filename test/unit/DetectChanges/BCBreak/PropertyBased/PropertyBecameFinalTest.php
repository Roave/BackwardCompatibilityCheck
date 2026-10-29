<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyBecameFinal;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use RoaveTest\BackwardCompatibility\TypeRestriction;

use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

#[CoversClass(PropertyBecameFinal::class)]
final class PropertyBecameFinalTest extends TestCase
{
    /** @param string[] $expectedMessages */
    #[DataProvider('propertiesToBeTested')]
    public function testDiffs(
        ReflectionProperty $fromProperty,
        ReflectionProperty $toProperty,
        array $expectedMessages,
    ): void {
        $changes = (new PropertyBecameFinal())($fromProperty, $toProperty);

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
    public static function propertiesToBeTested(): array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public int $publicOverridableToFinal;
    final public int $publicFinalToOverridable;
    public int $publicOverridableToOverridable;
    final public int $publicFinalToFinal;

    protected int $protectedOverridableToFinal;
    final protected int $protectedFinalToOverridable;
    protected int $protectedOverridableToOverridable;
    final protected int $protectedFinalToFinal;

    public int $publicOverridableToPrivateSet;
    public private(set) int $publicPrivateSetToOverridable;
    public int $publicOverridableToProtectedSet;

    public function __construct(
        public int $promotedOverridableToFinal,
        final public int $promotedFinalToOverridable,
    ) {}
}
PHP
            ,
            $astLocator,
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    final public int $publicOverridableToFinal;
    public int $publicFinalToOverridable;
    public int $publicOverridableToOverridable;
    final public int $publicFinalToFinal;

    final protected int $protectedOverridableToFinal;
    protected int $protectedFinalToOverridable;
    protected int $protectedOverridableToOverridable;
    final protected int $protectedFinalToFinal;

    public private(set) int $publicOverridableToPrivateSet;
    public int $publicPrivateSetToOverridable;
    public protected(set) int $publicOverridableToProtectedSet;

    public function __construct(
        final public int $promotedOverridableToFinal,
        public int $promotedFinalToOverridable,
    ) {}
}
PHP
            ,
            $astLocator,
        );

        $fromClassReflector = new DefaultReflector($fromLocator);
        $toClassReflector   = new DefaultReflector($toLocator);
        $fromClass          = $fromClassReflector->reflectClass('TheClass');
        $toClass            = $toClassReflector->reflectClass('TheClass');

        $properties = [
            'publicOverridableToFinal' => ['[BC] CHANGED: Property TheClass#$publicOverridableToFinal became final'],
            'publicFinalToOverridable' => [],
            'publicOverridableToOverridable' => [],
            'publicFinalToFinal' => [],
            'protectedOverridableToFinal' => ['[BC] CHANGED: Property TheClass#$protectedOverridableToFinal became final'],
            'protectedFinalToOverridable' => [],
            'protectedOverridableToOverridable' => [],
            'protectedFinalToFinal' => [],
            'publicOverridableToPrivateSet' => ['[BC] CHANGED: Property TheClass#$publicOverridableToPrivateSet became final'],
            'publicPrivateSetToOverridable' => [],
            'publicOverridableToProtectedSet' => [],
            'promotedOverridableToFinal' => ['[BC] CHANGED: Property TheClass#$promotedOverridableToFinal became final'],
            'promotedFinalToOverridable' => [],
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
