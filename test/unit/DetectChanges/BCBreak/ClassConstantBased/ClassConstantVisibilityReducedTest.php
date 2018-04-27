<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\DetectChanges\BCBreak\ClassConstantBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\DetectChanges\BCBreak\ClassConstantBased\ClassConstantVisibilityReduced;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClassConstant;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\ApiCompare\DetectChanges\BCBreak\ClassConstantBased\ClassConstantVisibilityReduced
 */
final class ClassConstantVisibilityReducedTest extends TestCase
{
    /**
     * @dataProvider propertiesToBeTested
     *
     * @param string[] $expectedMessages
     */
    public function testDiffs(
        ReflectionClassConstant $fromConstant,
        ReflectionClassConstant $toConstant,
        array $expectedMessages
    ) : void {
        $changes = (new ClassConstantVisibilityReduced())
            ->compare($fromConstant, $toConstant);

        self::assertSame(
            $expectedMessages,
            array_map(function (Change $change) : string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /** @return (string[]|ReflectionClassConstant)[][] */
    public function propertiesToBeTested() : array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public const publicMaintainedPublic = 'value';
    public const publicReducedToProtected = 'value';
    public const publicReducedToPrivate = 'value';
    protected const protectedMaintainedProtected = 'value';
    protected const protectedReducedToPrivate = 'value';
    protected const protectedIncreasedToPublic = 'value';
    private const privateMaintainedPrivate = 'value';
    private const privateIncreasedToProtected = 'value';
    private const privateIncreasedToPublic = 'value';
}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public const publicMaintainedPublic = 'value';
    protected const publicReducedToProtected = 'value';
    private const publicReducedToPrivate = 'value';
    protected const protectedMaintainedProtected = 'value';
    private const protectedReducedToPrivate = 'value';
    public const protectedIncreasedToPublic = 'value';
    private const privateMaintainedPrivate = 'value';
    protected const privateIncreasedToProtected = 'value';
    public const privateIncreasedToPublic = 'value';
}
PHP
            ,
            $astLocator
        );

        $fromClassReflector = new ClassReflector($fromLocator);
        $toClassReflector   = new ClassReflector($toLocator);
        $fromClass          = $fromClassReflector->reflect('TheClass');
        $toClass            = $toClassReflector->reflect('TheClass');

        $properties = [

            'publicMaintainedPublic' => [],
            'publicReducedToProtected' => [
                '[BC] CHANGED: Constant TheClass::publicReducedToProtected visibility reduced from public to protected',
            ],
            'publicReducedToPrivate' => [
                '[BC] CHANGED: Constant TheClass::publicReducedToPrivate visibility reduced from public to private',
            ],
            'protectedMaintainedProtected' => [],
            'protectedReducedToPrivate' => [
                '[BC] CHANGED: Constant TheClass::protectedReducedToPrivate visibility reduced from protected to private',
            ],
            'protectedIncreasedToPublic' => [],
            'privateMaintainedPrivate' => [],
            'privateIncreasedToProtected' => [],
            'privateIncreasedToPublic' => [],
        ];

        return array_combine(
            array_keys($properties),
            array_map(
                function (string $constant, array $errorMessages) use ($fromClass, $toClass) : array {
                    return [
                        $fromClass->getReflectionConstant($constant),
                        $toClass->getReflectionConstant($constant),
                        $errorMessages,
                    ];
                },
                array_keys($properties),
                $properties
            )
        );
    }
}
