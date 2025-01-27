<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodBased;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodParameterAdded;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_combine;
use function array_keys;
use function array_map;
use function assert;
use function iterator_to_array;

#[CoversClass(MethodParameterAdded::class)]
final class MethodParameterAddedTest extends TestCase
{
    private MethodBased $methodCheck;

    protected function setUp(): void
    {
        parent::setUp();

        $this->methodCheck = new MethodParameterAdded();
    }

    public function testWillSkipCheckingPrivateMethods(): void
    {
        $from = $this->createMock(ReflectionMethod::class);
        $to   = $this->createMock(ReflectionMethod::class);

        $from
            ->method('isPrivate')
            ->willReturn(true);

        self::assertEquals(Changes::empty(), ($this->methodCheck)($from, $to));
    }

    public function testWillSkipCheckingMethodsOnFinalClasses(): void
    {
        $from = $this->createMock(ReflectionMethod::class);
        $to   = $this->createMock(ReflectionMethod::class);

        $from
            ->method('isPrivate')
            ->willReturn(false);

        self::assertEquals(Changes::empty(), ($this->methodCheck)($from, $to));
    }

    public function testWillSkipCheckingPrivateMethodsOnFinalClasses(): void
    {
        $from = $this->createMock(ReflectionMethod::class);
        $to   = $this->createMock(ReflectionMethod::class);

        $from
            ->method('isPrivate')
            ->willReturn(true);

        $from
            ->method('isFinal')
            ->willReturn(true);

        self::assertEquals(Changes::empty(), ($this->methodCheck)($from, $to));
    }

    /** @param string[] $expectedMessages */
    #[DataProvider('methodsToBeTested')]
    public function testDiffs(
        ReflectionMethod $fromMethod,
        ReflectionMethod $toMethod,
        array $expectedMessages,
    ): void {
        $changes = (new MethodParameterAdded())($fromMethod, $toMethod);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes)),
        );
    }

    /** @return array<string, array{0: ReflectionMethod, 1: ReflectionMethod, 2: list<string>}> */
    public static function methodsToBeTested(): array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public function addedParameter() {}
    public function addedTwoParameters() {}
    public function addedAnotherParameter(int $one) {}
    public function noParameters() {}
    public function twoParams(int $one, int $two) {}
    private function privateMethod() {}
    public function removedParameter(int $one, array $options = []) {}
}
PHP
            ,
            $astLocator,
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public function addedParameter(array $options = []) {}
    public function addedTwoParameters(int $one, array $options = []) {}
    public function addedAnotherParameter(int $one, array $options = []) {}
    public function noParameters() {}
    public function twoParams(int $one, int $two) {}
    private function privateMethod(array $options = []) {}
    public function removedParameter(int $one) {}
}
PHP
            ,
            $astLocator,
        );

        $fromClassReflector = new DefaultReflector($fromLocator);
        $toClassReflector   = new DefaultReflector($toLocator);
        $fromClass          = $fromClassReflector->reflectClass('TheClass');
        $toClass            = $toClassReflector->reflectClass('TheClass');

        $methods = [
            'addedParameter' => ['[BC] ADDED: Parameter options was added to Method addedParameter() of class TheClass'],
            'addedTwoParameters' => [
                '[BC] ADDED: Parameter one was added to Method addedTwoParameters() of class TheClass',
                '[BC] ADDED: Parameter options was added to Method addedTwoParameters() of class TheClass',
            ],
            'addedAnotherParameter' => ['[BC] ADDED: Parameter options was added to Method addedAnotherParameter() of class TheClass'],
            'noParameters' => [],
            'privateMethod' => [],
            'removedParameter' => [],
            'twoParams' => [],
        ];

        return array_combine(
            array_keys($methods),
            array_map(
                static fn (string $methodName, array $errors): array => [
                    self::getMethod($fromClass, $methodName),
                    self::getMethod($toClass, $methodName),
                    $errors,
                ],
                array_keys($methods),
                $methods,
            ),
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
