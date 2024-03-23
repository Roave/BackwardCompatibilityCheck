<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ParameterDefaultValueChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Throwable;

use function array_combine;
use function array_keys;
use function array_map;
use function array_merge;
use function assert;
use function iterator_to_array;

/** @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\FunctionBased\ParameterDefaultValueChanged */
final class ParameterDefaultValueChangedTest extends TestCase
{
    /**
     * @param string[] $expectedMessages
     *
     * @dataProvider functionsToBeTested
     */
    public function testDiffs(
        ReflectionMethod|ReflectionFunction $fromFunction,
        ReflectionMethod|ReflectionFunction $toFunction,
        array $expectedMessages,
    ): void {
        $changes = (new ParameterDefaultValueChanged())($fromFunction, $toFunction);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes)),
        );
    }

    public function testDefaultValueChangeCausesUnableToCompileNodeException(): void
    {
        $originalSource = <<<'PHP'
        <?php

        class OriginalClass {
            public function methodWithDefaultValue($param = SOME_CONSTANT) {}
        }
    PHP;

        $modifiedSource = <<<'PHP'
        <?php

        class ModifiedClass {
            // Introducing a minor change that still relies on an undefined constant
            public function methodWithDefaultValue($param = SOME_CONSTANT + 1) {}
        }
    PHP;

        $astLocator        = (new BetterReflection())->astLocator();
        $originalReflector = new DefaultReflector(new StringSourceLocator($originalSource, $astLocator));
        $modifiedReflector = new DefaultReflector(new StringSourceLocator($modifiedSource, $astLocator));

        $default = ReflectionMethod::createFromName(Throwable::class, 'getMessage');

        $fromMethod = $originalReflector
            ->reflectClass('OriginalClass')
            ->getMethod('methodWithDefaultValue') ?? $default;

        $toMethod = $modifiedReflector
            ->reflectClass('ModifiedClass')
            ->getMethod('methodWithDefaultValue') ?? $default;

        $checker = new ParameterDefaultValueChanged();

        $this->expectException(UnableToCompileNode::class);

        $checker($fromMethod, $toMethod);
    }

    /**
     * @return array<string, array{
     *     0: ReflectionMethod|ReflectionFunction,
     *     1: ReflectionMethod|ReflectionFunction,
     *     2: list<string>
     * }>
     */
    public function functionsToBeTested(): array
    {
        $astLocator    = (new BetterReflection())->astLocator();
        $sourceStubber = (new BetterReflection())->sourceStubber();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace {
   function changed($a = 1) {}
   function defaultAdded($a) {}
   function defaultRemoved($a = null) {}
   function defaultTypeChanged($a = '1') {}
   function notChanged($a = 1, $b = 2, $c = 3) {}
   function namesChanged($a = 1, $b = 2, $c = 3) {}
   function orderChanged($a = 1, $b = 2, $c = 3) {}
   function positionOfOptionalParameterChanged($a = 2, $b, $c = 1) {}
   class C {
       static function changed1($a = 1) {}
       function changed2($a = 1) {}
       function notChangedNewInitializer($a = new stdClass()) {}
       function notChangedNewEnum($a = D::A) {}
       function changedNewEnumAndNewInitializer() {}
       function changedRemovedNewEnumAndNewInitializer($a = D::A, $b = new stdClass()) {}
   }
    enum D: string {
        case A = 'A';
    }
}
PHP
            ,
            $astLocator,
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

namespace {
   function changed($a = 2) {}
   function defaultAdded($a = 1) {}
   function defaultRemoved($a) {}
   function defaultTypeChanged($a = 1) {}
   function notChanged($a = 1, $b = 2, $c = 3) {}
   function namesChanged($d = 1, $e = 2, $f = 3) {}
   function orderChanged($c = 3, $b = 2, $a = 1) {}
   function positionOfOptionalParameterChanged($a, $b = 2, $c = 1) {}
   class C {
       static function changed1($a = 2) {}
       function changed2($a = 2) {}
       function notChangedNewInitializer($a = new stdClass()) {}
       function notChangedNewEnum($a = D::A) {}
       function changedNewEnumAndNewInitializer($a = D::A, $b = new stdClass()) {}
       function changedRemovedNewEnumAndNewInitializer() {}
  }
   enum D: string {
        case A = 'A';
   }
}
PHP
            ,
            $astLocator,
        );

        $fromReflector = new DefaultReflector(new AggregateSourceLocator([
            $fromLocator,
            new PhpInternalSourceLocator($astLocator, $sourceStubber),
        ]));
        $toReflector   = new DefaultReflector(new AggregateSourceLocator([
            $toLocator,
            new PhpInternalSourceLocator($astLocator, $sourceStubber),
        ]));

        $functions = [
            'changed'            => ['[BC] CHANGED: Default parameter value for parameter $a of changed() changed from 1 to 2'],
            'defaultAdded'       => [],
            'defaultRemoved'     => [],
            'defaultTypeChanged' => ['[BC] CHANGED: Default parameter value for parameter $a of defaultTypeChanged() changed from \'1\' to 1'],
            'notChanged'         => [],
            'namesChanged'       => [],
            'orderChanged'       => [
                '[BC] CHANGED: Default parameter value for parameter $a of orderChanged() changed from 1 to 3',
                '[BC] CHANGED: Default parameter value for parameter $c of orderChanged() changed from 3 to 1',
            ],
            'positionOfOptionalParameterChanged' => [],
        ];

        return array_merge(
            array_combine(
                array_keys($functions),
                array_map(
                    static fn (string $function, array $errors): array => [
                        $fromReflector->reflectFunction($function),
                        $toReflector->reflectFunction($function),
                        $errors,
                    ],
                    array_keys($functions),
                    $functions,
                ),
            ),
            [
                'C::changed1' => [
                    self::getMethod($fromReflector->reflectClass('C'), 'changed1'),
                    self::getMethod($toReflector->reflectClass('C'), 'changed1'),
                    ['[BC] CHANGED: Default parameter value for parameter $a of C::changed1() changed from 1 to 2'],
                ],
                'C#changed2'  => [
                    self::getMethod($fromReflector->reflectClass('C'), 'changed2'),
                    self::getMethod($toReflector->reflectClass('C'), 'changed2'),
                    ['[BC] CHANGED: Default parameter value for parameter $a of C#changed2() changed from 1 to 2'],
                ],
                'C#notChangedNewInitializer'  => [
                    self::getMethod($fromReflector->reflectClass('C'), 'notChangedNewInitializer'),
                    self::getMethod($toReflector->reflectClass('C'), 'notChangedNewInitializer'),
                    [],
                ],
                'C#notChangedNewEnum'  => [
                    self::getMethod($fromReflector->reflectClass('C'), 'notChangedNewEnum'),
                    self::getMethod($toReflector->reflectClass('C'), 'notChangedNewEnum'),
                    [],
                ],
                'C#changedNewEnumAndNewInitializer'  => [
                    self::getMethod($fromReflector->reflectClass('C'), 'changedNewEnumAndNewInitializer'),
                    self::getMethod($toReflector->reflectClass('C'), 'changedNewEnumAndNewInitializer'),
                    [],
                ],
                'C#changedRemovedNewEnumAndNewInitializer'  => [
                    self::getMethod($fromReflector->reflectClass('C'), 'changedRemovedNewEnumAndNewInitializer'),
                    self::getMethod($toReflector->reflectClass('C'), 'changedRemovedNewEnumAndNewInitializer'),
                    [],
                ],
            ],
        );
    }

    /** @param non-empty-string $name */
    private static function getMethod(ReflectionClass $class, string $name): ReflectionMethod
    {
        $method = $class->getMethod($name);

        assert($method !== null);

        return $method;
    }
}
