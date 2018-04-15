<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\ConstantVisibilityReduced;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

final class ConstantVisibilityReducedTest extends TestCase
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
        $changes = (new ConstantVisibilityReduced())
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
            'RoaveTestAsset\\ClassWithConstantVisibilitiesBeingChanged' => [
                (new ClassReflector(new SingleFileSourceLocator(
                    __DIR__ . '/../../../../asset/api/old/ClassWithConstantVisibilitiesBeingChanged.php',
                    $locator
                )))->reflect('RoaveTestAsset\\ClassWithConstantVisibilitiesBeingChanged'),
                (new ClassReflector(new SingleFileSourceLocator(
                    __DIR__ . '/../../../../asset/api/new/ClassWithConstantVisibilitiesBeingChanged.php',
                    $locator
                )))->reflect('RoaveTestAsset\\ClassWithConstantVisibilitiesBeingChanged'),
                [
                    '[BC] CHANGED: Constant RoaveTestAsset\ClassWithConstantVisibilitiesBeingChanged::publicReducedToProtected changed visibility from public to protected',
                    '[BC] CHANGED: Constant RoaveTestAsset\ClassWithConstantVisibilitiesBeingChanged::publicReducedToPrivate changed visibility from public to private',
                    '[BC] CHANGED: Constant RoaveTestAsset\ClassWithConstantVisibilitiesBeingChanged::protectedReducedToPrivate changed visibility from protected to private',
                ],
            ],
        ];
    }
}
