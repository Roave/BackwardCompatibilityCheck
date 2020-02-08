<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\InterfaceBecameTrait;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use RoaveTest\BackwardCompatibility\TypeRestriction;
use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

final class InterfaceBecameTraitTest extends TestCase
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
        $changes = (new InterfaceBecameTrait())
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

class ConcreteToAbstract {}
abstract class AbstractToConcrete {}
class ConcreteToConcrete {}
abstract class AbstractToAbstract {}
class ConcreteToInterface {}
interface InterfaceToConcrete {}
interface InterfaceToInterface {}
interface InterfaceToAbstract {}
abstract class AbstractToInterface {}
interface InterfaceToTrait {}
trait TraitToInterface {}
trait TraitToTrait {}
PHP
            ,
            $locator
        ));
        $toReflector   = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

abstract class ConcreteToAbstract {}
class AbstractToConcrete {}
class ConcreteToConcrete {}
abstract class AbstractToAbstract {}
interface ConcreteToInterface {}
class InterfaceToConcrete {}
interface InterfaceToInterface {}
abstract class InterfaceToAbstract {}
interface AbstractToInterface {}
trait InterfaceToTrait {}
interface TraitToInterface {}
trait TraitToTrait {}
PHP
            ,
            $locator
        ));

        $classes = [
            'ConcreteToAbstract'   => [],
            'AbstractToConcrete'   => [],
            'ConcreteToConcrete'   => [],
            'AbstractToAbstract'   => [],
            'ConcreteToInterface'  => [],
            'InterfaceToConcrete'  => [],
            'InterfaceToInterface' => [],
            'InterfaceToAbstract'  => [],
            'AbstractToInterface'  => [],
            'InterfaceToTrait'     => ['[BC] CHANGED: Interface InterfaceToTrait became a trait'],
            'TraitToInterface'     => [],
            'TraitToTrait'         => [],
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
