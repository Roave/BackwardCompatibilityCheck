<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\TraitBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased\TraitBecameInterface;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use RoaveTest\BackwardCompatibility\TypeRestriction;
use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased\TraitBecameInterface
 */
final class TraitBecameInterfaceTest extends TestCase
{
    /**
     * @param string[] $expectedMessages
     *
     * @dataProvider classesToBeTested
     */
    public function testDiffs(
        ReflectionClass $fromClass,
        ReflectionClass $toClass,
        array $expectedMessages
    ) : void {
        $changes = (new TraitBecameInterface())
            ->__invoke($fromClass, $toClass);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change) : string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /**
     * @return array<string, array<int, ReflectionClass|array<int, string>>>
     *
     * @psalm-return array<string, array{0: ReflectionClass, 1: ReflectionClass, 2: list<string>}>
     */
    public function classesToBeTested() : array
    {
        $locator       = (new BetterReflection())->astLocator();
        $fromReflector = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

trait TraitToClass {}
trait TraitToInterface {}
class ClassToTrait {}
trait TraitToTrait {}
class ClassToClass {}
interface InterfaceToTrait {}
interface InterfaceToInterface {}
PHP
            ,
            $locator
        ));
        $toReflector   = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

class TraitToClass {}
interface TraitToInterface {}
trait ClassToTrait {}
trait TraitToTrait {}
class ClassToClass {}
trait InterfaceToTrait {}
interface InterfaceToInterface {}
PHP
            ,
            $locator
        ));

        $classes = [
            'TraitToClass'         => [],
            'TraitToInterface'     => ['[BC] CHANGED: Interface TraitToInterface became an interface'],
            'ClassToTrait'         => [],
            'TraitToTrait'         => [],
            'ClassToClass'         => [],
            'InterfaceToTrait'     => [],
            'InterfaceToInterface' => [],
        ];

        return TypeRestriction::array(array_combine(
            array_keys($classes),
            array_map(
                /** @psalm-param list<string> $errors https://github.com/vimeo/psalm/issues/2772 */
                static function (string $className, array $errors) use ($fromReflector, $toReflector) : array {
                    return [
                        $fromReflector->reflect($className),
                        $toReflector->reflect($className),
                        $errors,
                    ];
                },
                array_keys($classes),
                $classes
            )
        ));
    }
}
