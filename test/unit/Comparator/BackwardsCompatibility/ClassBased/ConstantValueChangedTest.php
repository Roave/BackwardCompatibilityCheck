<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\ConstantValueChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

final class ConstantValueChangedTest extends TestCase
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
        $changes = (new ConstantValueChanged())
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
            'RoaveTestAsset\\ClassWithConstantValuesBeingChanged' => [
                (new ClassReflector(new SingleFileSourceLocator(
                    __DIR__ . '/../../../../asset/api/old/ClassWithConstantValuesBeingChanged.php',
                    $locator
                )))->reflect('RoaveTestAsset\\ClassWithConstantValuesBeingChanged'),
                (new ClassReflector(new SingleFileSourceLocator(
                    __DIR__ . '/../../../../asset/api/new/ClassWithConstantValuesBeingChanged.php',
                    $locator
                )))->reflect('RoaveTestAsset\\ClassWithConstantValuesBeingChanged'),
                [
                    '[BC] CHANGED: Value of constant RoaveTestAsset\ClassWithConstantValuesBeingChanged::changedPublicConstant changed',
                    '[BC] CHANGED: Value of constant RoaveTestAsset\ClassWithConstantValuesBeingChanged::changedProtectedConstant changed',
                    '[BC] CHANGED: Value of constant RoaveTestAsset\ClassWithConstantValuesBeingChanged::publicConstantChangedToNull changed',
                ],
            ],
        ];
    }
}
