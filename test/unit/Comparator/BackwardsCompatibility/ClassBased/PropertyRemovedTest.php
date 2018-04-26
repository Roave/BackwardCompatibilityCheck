<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\PropertyRemoved;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use function array_map;
use function iterator_to_array;

final class PropertyRemovedTest extends TestCase
{
    /**
     * @dataProvider classesToBeTested
     *
     * @param string[] $expectedMessages
     */
    public function testDiffs(
        ReflectionClass $fromClass,
        ReflectionClass $toClass,
        array $expectedMessages
    ) : void {
        $changes = (new PropertyRemoved())
            ->compare($fromClass, $toClass);

        self::assertSame(
            $expectedMessages,
            array_map(function (Change $change) : string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /** @return (string[]|ReflectionClass)[][] */
    public function classesToBeTested() : array
    {
        $locator = (new BetterReflection())->astLocator();

        return [
            'RoaveTestAsset\\ClassWithPropertiesBeingRemoved' => [
                (new ClassReflector(new SingleFileSourceLocator(
                    __DIR__ . '/../../../../asset/api/old/ClassWithPropertiesBeingRemoved.php',
                    $locator
                )))->reflect('RoaveTestAsset\\ClassWithPropertiesBeingRemoved'),
                (new ClassReflector(new SingleFileSourceLocator(
                    __DIR__ . '/../../../../asset/api/new/ClassWithPropertiesBeingRemoved.php',
                    $locator
                )))->reflect('RoaveTestAsset\\ClassWithPropertiesBeingRemoved'),
                [
                    '[BC] REMOVED: Property RoaveTestAsset\ClassWithPropertiesBeingRemoved#$removedPublicProperty was removed',
                    '[BC] REMOVED: Property RoaveTestAsset\ClassWithPropertiesBeingRemoved#$nameCaseChangePublicProperty was removed',
                    '[BC] REMOVED: Property RoaveTestAsset\ClassWithPropertiesBeingRemoved#$removedProtectedProperty was removed',
                    '[BC] REMOVED: Property RoaveTestAsset\ClassWithPropertiesBeingRemoved#$nameCaseChangeProtectedProperty was removed',
                ],
            ],
        ];
    }
}
