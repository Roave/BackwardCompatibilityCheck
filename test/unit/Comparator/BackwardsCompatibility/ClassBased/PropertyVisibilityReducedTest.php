<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\PropertyVisibilityReduced;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

final class PropertyVisibilityReducedTest extends TestCase
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
        $changes = (new PropertyVisibilityReduced())
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
            'RoaveTestAsset\\ClassWithPropertyVisibilitiesBeingChanged' => [
                (new ClassReflector(new SingleFileSourceLocator(
                    __DIR__ . '/../../../../asset/api/old/ClassWithPropertyVisibilitiesBeingChanged.php',
                    $locator
                )))->reflect('RoaveTestAsset\\ClassWithPropertyVisibilitiesBeingChanged'),
                (new ClassReflector(new SingleFileSourceLocator(
                    __DIR__ . '/../../../../asset/api/new/ClassWithPropertyVisibilitiesBeingChanged.php',
                    $locator
                )))->reflect('RoaveTestAsset\\ClassWithPropertyVisibilitiesBeingChanged'),
                [
                    '[BC] REMOVED: Property RoaveTestAsset\ClassWithPropertyVisibilitiesBeingChanged#publicReducedToProtected changed visibility from public to protected',
                    '[BC] REMOVED: Property RoaveTestAsset\ClassWithPropertyVisibilitiesBeingChanged#publicReducedToPrivate changed visibility from public to private',
                    '[BC] REMOVED: Property RoaveTestAsset\ClassWithPropertyVisibilitiesBeingChanged#protectedReducedToPrivate changed visibility from protected to private',
                ],
            ],
        ];
    }
}
