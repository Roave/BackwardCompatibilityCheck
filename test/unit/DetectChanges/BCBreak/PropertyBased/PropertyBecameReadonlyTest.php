<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyBecameReadonly;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use RoaveTest\BackwardCompatibility\TypeRestriction;

use function array_map;
use function iterator_to_array;

#[CoversClass(PropertyBecameReadonly::class)]
final class PropertyBecameReadonlyTest extends TestCase
{
    /** @param string[] $expectedMessages */
    #[DataProvider('propertiesToBeTested')]
    public function testDiffs(
        ReflectionProperty $fromProperty,
        ReflectionProperty $toProperty,
        array $expectedMessages,
    ): void {
        $changes = (new PropertyBecameReadonly())($fromProperty, $toProperty);

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
    public int $publicMutableToReadonly;
    public readonly int $publicReadonlyToMutable;
    public int $publicMutableToMutable;
    public readonly int $publicReadonlyToReadonly;

    protected int $protectedMutableToReadonly;
    protected readonly int $protectedReadonlyToMutable;
    protected int $protectedMutableToMutable;
    protected readonly int $protectedReadonlyToReadonly;

    private int $privateMutableToReadonly;
    private readonly int $privateReadonlyToMutable;
    private int $privateMutableToMutable;
    private readonly int $privateReadonlyToReadonly;

    public function __construct(
        public int $promotedMutableToReadonly,
        public readonly int $promotedReadonlyToMutable,
    ) {}
}

class ClassBecomingReadonly {
    public int $mutableToReadonlyThroughClass;
    public readonly int $readonlyToReadonlyThroughClass;
}

readonly class ClassNoLongerReadonly {
    public int $readonlyThroughClassToMutable;
}
PHP
            ,
            $astLocator,
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public readonly int $publicMutableToReadonly;
    public int $publicReadonlyToMutable;
    public int $publicMutableToMutable;
    public readonly int $publicReadonlyToReadonly;

    protected readonly int $protectedMutableToReadonly;
    protected int $protectedReadonlyToMutable;
    protected int $protectedMutableToMutable;
    protected readonly int $protectedReadonlyToReadonly;

    private readonly int $privateMutableToReadonly;
    private int $privateReadonlyToMutable;
    private int $privateMutableToMutable;
    private readonly int $privateReadonlyToReadonly;

    public function __construct(
        public readonly int $promotedMutableToReadonly,
        public int $promotedReadonlyToMutable,
    ) {}
}

readonly class ClassBecomingReadonly {
    public int $mutableToReadonlyThroughClass;
    public int $readonlyToReadonlyThroughClass;
}

class ClassNoLongerReadonly {
    public int $readonlyThroughClassToMutable;
}
PHP
            ,
            $astLocator,
        );

        $fromClassReflector = new DefaultReflector($fromLocator);
        $toClassReflector   = new DefaultReflector($toLocator);

        $properties = [
            'TheClass' => [
                'publicMutableToReadonly' => ['[BC] CHANGED: Property TheClass#$publicMutableToReadonly became readonly'],
                'publicReadonlyToMutable' => [],
                'publicMutableToMutable' => [],
                'publicReadonlyToReadonly' => [],
                'protectedMutableToReadonly' => ['[BC] CHANGED: Property TheClass#$protectedMutableToReadonly became readonly'],
                'protectedReadonlyToMutable' => [],
                'protectedMutableToMutable' => [],
                'protectedReadonlyToReadonly' => [],
                'privateMutableToReadonly' => ['[BC] CHANGED: Property TheClass#$privateMutableToReadonly became readonly'],
                'privateReadonlyToMutable' => [],
                'privateMutableToMutable' => [],
                'privateReadonlyToReadonly' => [],
                'promotedMutableToReadonly' => ['[BC] CHANGED: Property TheClass#$promotedMutableToReadonly became readonly'],
                'promotedReadonlyToMutable' => [],
            ],
            'ClassBecomingReadonly' => [
                'mutableToReadonlyThroughClass' => ['[BC] CHANGED: Property ClassBecomingReadonly#$mutableToReadonlyThroughClass became readonly'],
                'readonlyToReadonlyThroughClass' => [],
            ],
            'ClassNoLongerReadonly' => [
                'readonlyThroughClassToMutable' => [],
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
