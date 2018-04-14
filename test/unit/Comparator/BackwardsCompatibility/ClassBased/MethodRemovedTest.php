<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\ClassBased\MethodRemoved;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

final class MethodRemovedTest extends TestCase
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
        $changes = (new MethodRemoved())
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
            'RoaveTestAsset\\ClassWithMethodsBeingRemoved' => [
                (new ClassReflector(new SingleFileSourceLocator(
                    __DIR__ . '/../../../../asset/api/old/ClassWithMethodsBeingRemoved.php',
                    $locator
                )))->reflect('RoaveTestAsset\\ClassWithMethodsBeingRemoved'),
                (new ClassReflector(new SingleFileSourceLocator(
                    __DIR__ . '/../../../../asset/api/new/ClassWithMethodsBeingRemoved.php',
                    $locator
                )))->reflect('RoaveTestAsset\\ClassWithMethodsBeingRemoved'),
                [
                    '[BC] REMOVED: Method RoaveTestAsset\ClassWithMethodsBeingRemoved#removedPublicMethod() was removed',
                    '[BC] REMOVED: Method RoaveTestAsset\ClassWithMethodsBeingRemoved#removedProtectedMethod() was removed',
                ],
            ],
        ];
    }
}
