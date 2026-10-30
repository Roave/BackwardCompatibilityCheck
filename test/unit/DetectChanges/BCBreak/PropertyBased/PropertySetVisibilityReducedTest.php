<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertySetVisibilityReduced;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use RoaveTest\BackwardCompatibility\TypeRestriction;

use function array_map;
use function iterator_to_array;

#[CoversClass(PropertySetVisibilityReduced::class)]
final class PropertySetVisibilityReducedTest extends TestCase
{
    /** @param string[] $expectedMessages */
    #[DataProvider('propertiesToBeTested')]
    public function testDiffs(
        ReflectionProperty $fromProperty,
        ReflectionProperty $toProperty,
        array $expectedMessages,
    ): void {
        $changes = (new PropertySetVisibilityReduced())($fromProperty, $toProperty);

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
    public int $publicMaintainedPublic;
    public int $publicReducedToProtectedSet;
    public int $publicReducedToPrivateSet;
    public protected(set) int $protectedSetMaintainedProtectedSet;
    public protected(set) int $protectedSetReducedToPrivateSet;
    public protected(set) int $protectedSetIncreasedToPublic;
    public private(set) int $privateSetMaintainedPrivateSet;
    public private(set) int $privateSetIncreasedToProtectedSet;
    public private(set) int $privateSetIncreasedToPublic;

    public int $publicReducedToProtected;
    public int $publicReducedToProtectedWithPrivateSet;
    public protected(set) int $protectedSetReducedToProtected;
    protected int $protectedReducedToPrivateSet;

    public int $publicBecameReadonly;
    public int $publicBecameProtectedSetReadonly;
    public int $publicBecamePrivateSetReadonly;
    public readonly int $readonlyReducedToPrivateSet;
    public readonly int $readonlyBecameMutable;

    public function __construct(
        public int $promotedPublicReducedToPrivateSet,
    ) {}
}

final class TheFinalClass {
    public int $finalPublicReducedToProtectedSet;
    public int $finalPublicReducedToPrivateSet;
    public protected(set) int $finalProtectedSetReducedToPrivateSet;
    public readonly int $finalReadonlyReducedToPrivateSet;
}

class ClassBecomingReadonly {
    public int $publicBecameReadonlyThroughClass;
}
PHP
            ,
            $astLocator,
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public int $publicMaintainedPublic;
    public protected(set) int $publicReducedToProtectedSet;
    public private(set) int $publicReducedToPrivateSet;
    public protected(set) int $protectedSetMaintainedProtectedSet;
    public private(set) int $protectedSetReducedToPrivateSet;
    public int $protectedSetIncreasedToPublic;
    public private(set) int $privateSetMaintainedPrivateSet;
    public protected(set) int $privateSetIncreasedToProtectedSet;
    public int $privateSetIncreasedToPublic;

    protected int $publicReducedToProtected;
    protected private(set) int $publicReducedToProtectedWithPrivateSet;
    protected int $protectedSetReducedToProtected;
    protected private(set) int $protectedReducedToPrivateSet;

    public readonly int $publicBecameReadonly;
    public protected(set) readonly int $publicBecameProtectedSetReadonly;
    public private(set) readonly int $publicBecamePrivateSetReadonly;
    public private(set) readonly int $readonlyReducedToPrivateSet;
    public int $readonlyBecameMutable;

    public function __construct(
        public private(set) int $promotedPublicReducedToPrivateSet,
    ) {}
}

final class TheFinalClass {
    public protected(set) int $finalPublicReducedToProtectedSet;
    public private(set) int $finalPublicReducedToPrivateSet;
    public private(set) int $finalProtectedSetReducedToPrivateSet;
    public private(set) readonly int $finalReadonlyReducedToPrivateSet;
}

readonly class ClassBecomingReadonly {
    public int $publicBecameReadonlyThroughClass;
}
PHP
            ,
            $astLocator,
        );

        $fromClassReflector = new DefaultReflector($fromLocator);
        $toClassReflector   = new DefaultReflector($toLocator);

        $properties = [
            'TheClass' => [
                'publicMaintainedPublic' => [],
                'publicReducedToProtectedSet' => ['[BC] CHANGED: Property TheClass#$publicReducedToProtectedSet set visibility reduced from public to protected'],
                'publicReducedToPrivateSet' => ['[BC] CHANGED: Property TheClass#$publicReducedToPrivateSet set visibility reduced from public to private'],
                'protectedSetMaintainedProtectedSet' => [],
                'protectedSetReducedToPrivateSet' => ['[BC] CHANGED: Property TheClass#$protectedSetReducedToPrivateSet set visibility reduced from protected to private'],
                'protectedSetIncreasedToPublic' => [],
                'privateSetMaintainedPrivateSet' => [],
                'privateSetIncreasedToProtectedSet' => [],
                'privateSetIncreasedToPublic' => [],
                'publicReducedToProtected' => [],
                'publicReducedToProtectedWithPrivateSet' => ['[BC] CHANGED: Property TheClass#$publicReducedToProtectedWithPrivateSet set visibility reduced from public to private'],
                'protectedSetReducedToProtected' => [],
                'protectedReducedToPrivateSet' => ['[BC] CHANGED: Property TheClass#$protectedReducedToPrivateSet set visibility reduced from protected to private'],
                'publicBecameReadonly' => [],
                'publicBecameProtectedSetReadonly' => [],
                'publicBecamePrivateSetReadonly' => ['[BC] CHANGED: Property TheClass#$publicBecamePrivateSetReadonly set visibility reduced from public to private'],
                'readonlyReducedToPrivateSet' => ['[BC] CHANGED: Property TheClass#$readonlyReducedToPrivateSet set visibility reduced from protected to private'],
                'readonlyBecameMutable' => [],
                'promotedPublicReducedToPrivateSet' => ['[BC] CHANGED: Property TheClass#$promotedPublicReducedToPrivateSet set visibility reduced from public to private'],
            ],
            'TheFinalClass' => [
                'finalPublicReducedToProtectedSet' => ['[BC] CHANGED: Property TheFinalClass#$finalPublicReducedToProtectedSet set visibility reduced from public to protected'],
                'finalPublicReducedToPrivateSet' => ['[BC] CHANGED: Property TheFinalClass#$finalPublicReducedToPrivateSet set visibility reduced from public to private'],
                'finalProtectedSetReducedToPrivateSet' => [],
                'finalReadonlyReducedToPrivateSet' => [],
            ],
            'ClassBecomingReadonly' => [
                'publicBecameReadonlyThroughClass' => [],
            ],
        ];

        $testCases = [];

        foreach ($properties as $className => $propertiesOfClass) {
            $fromClass = $fromClassReflector->reflectClass($className);
            $toClass   = $toClassReflector->reflectClass($className);

            foreach ($propertiesOfClass as $property => $errorMessages) {
                $testCases[$property] = [
                    TypeRestriction::object($fromClass->getProperty($property)),
                    TypeRestriction::object($toClass->getProperty($property)),
                    $errorMessages,
                ];
            }
        }

        return $testCases;
    }
}
