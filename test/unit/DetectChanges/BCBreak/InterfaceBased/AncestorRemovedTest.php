<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\AncestorRemoved;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\AncestorRemoved
 */
final class AncestorRemovedTest extends TestCase
{
    /**
     * @param string[] $expectedMessages
     *
     * @dataProvider interfacesToBeTested
     */
    public function testDiffs(
        ReflectionClass $fromInterface,
        ReflectionClass $toInterace,
        array $expectedMessages
    ): void {
        $changes = (new AncestorRemoved())($fromInterface, $toInterace);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /**
     * @return array<string, array<int, ReflectionClass|array<int, string>>>
     * @psalm-return array<string, array{0: ReflectionClass, 1: ReflectionClass, 2: list<string>}>
     */
    public function interfacesToBeTested(): array
    {
        $locator       = (new BetterReflection())->astLocator();
        $fromReflector = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

interface IA {}
interface IB extends IA {}
interface IC extends IB {}
interface ID {}
interface ParentInterfaceAdded {}
interface ParentInterfaceRemoved extends IA {}
interface ParentInterfaceIndirectlyRemoved extends IB {}
interface ParentInterfaceVeryIndirectlyRemoved extends IC {}
interface ParentInterfaceOrderSwapped extends IA, ID {}
PHP
            ,
            $locator
        ));
        $toReflector   = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

interface IA {}
interface IB {}
interface IC extends IB {}
interface ID {}
interface ParentInterfaceAdded extends IA {}
interface ParentInterfaceRemoved {}
interface ParentInterfaceIndirectlyRemoved extends IB {}
interface ParentInterfaceVeryIndirectlyRemoved extends IC {}
interface ParentInterfaceOrderSwapped extends ID, IA {}
PHP
            ,
            $locator
        ));

        $interfaces = [
            'IA' => [],
            'IB' => ['[BC] REMOVED: These ancestors of IB have been removed: ["IA"]'],
            'IC' => ['[BC] REMOVED: These ancestors of IC have been removed: ["IA"]'],
            'ParentInterfaceAdded' => [],
            'ParentInterfaceRemoved' => ['[BC] REMOVED: These ancestors of ParentInterfaceRemoved have been removed: ["IA"]'],
            'ParentInterfaceIndirectlyRemoved' => ['[BC] REMOVED: These ancestors of ParentInterfaceIndirectlyRemoved have been removed: ["IA"]'],
            'ParentInterfaceVeryIndirectlyRemoved' => ['[BC] REMOVED: These ancestors of ParentInterfaceVeryIndirectlyRemoved have been removed: ["IA"]'],
            'ParentInterfaceOrderSwapped' => [],
        ];

        return array_combine(
            array_keys($interfaces),
            array_map(
                /** @psalm-param list<string> $errors https://github.com/vimeo/psalm/issues/2772 */
                static function (string $interfaceName, array $errors) use ($fromReflector, $toReflector): array {
                    return [
                        $fromReflector->reflectClass($interfaceName),
                        $toReflector->reflectClass($interfaceName),
                        $errors,
                    ];
                },
                array_keys($interfaces),
                $interfaces
            )
        );
    }
}
