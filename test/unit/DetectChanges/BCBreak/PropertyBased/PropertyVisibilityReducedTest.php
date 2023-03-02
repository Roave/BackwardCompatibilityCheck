<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyVisibilityReduced;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use RoaveTest\BackwardCompatibility\TypeRestriction;

use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

/** @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyVisibilityReduced */
final class PropertyVisibilityReducedTest extends TestCase
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
        $changes = (new PropertyVisibilityReduced())($fromProperty, $toProperty);

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
    public $publicMaintainedPublic;
    public $publicReducedToProtected;
    public $publicReducedToPrivate;
    protected $protectedMaintainedProtected;
    protected $protectedReducedToPrivate;
    protected $protectedIncreasedToPublic;
    private $privateMaintainedPrivate;
    private $privateIncreasedToProtected;
    private $privateIncreasedToPublic;
}
PHP
            ,
            $astLocator,
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public $publicMaintainedPublic;
    protected $publicReducedToProtected;
    private $publicReducedToPrivate;
    protected $protectedMaintainedProtected;
    private $protectedReducedToPrivate;
    public $protectedIncreasedToPublic;
    private $privateMaintainedPrivate;
    protected $privateIncreasedToProtected;
    public $privateIncreasedToPublic;
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
            'publicMaintainedPublic' => [],
            'publicReducedToProtected' => ['[BC] CHANGED: Property TheClass#$publicReducedToProtected visibility reduced from public to protected'],
            'publicReducedToPrivate' => ['[BC] CHANGED: Property TheClass#$publicReducedToPrivate visibility reduced from public to private'],
            'protectedMaintainedProtected' => [],
            'protectedReducedToPrivate' => ['[BC] CHANGED: Property TheClass#$protectedReducedToPrivate visibility reduced from protected to private'],
            'protectedIncreasedToPublic' => [],
            'privateMaintainedPrivate' => [],
            'privateIncreasedToProtected' => [],
            'privateIncreasedToPublic' => [],
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
