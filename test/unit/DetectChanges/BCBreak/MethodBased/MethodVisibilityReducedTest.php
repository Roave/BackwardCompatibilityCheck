<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\MethodBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodVisibilityReduced;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

/** @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\MethodBased\MethodVisibilityReduced */
final class MethodVisibilityReducedTest extends TestCase
{
    /**
     * @param string[] $expectedMessages
     *
     * @dataProvider propertiesToBeTested
     */
    public function testDiffs(
        ReflectionMethod $fromMethod,
        ReflectionMethod $toMethod,
        array $expectedMessages,
    ): void {
        $changes = (new MethodVisibilityReduced())($fromMethod, $toMethod);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes)),
        );
    }

    /**
     * @return array<string, array<int, ReflectionMethod|array<int, string>>>
     * @psalm-return array<string, array{0: ReflectionMethod, 1: ReflectionMethod, 2: list<string>}>
     */
    public function propertiesToBeTested(): array
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public function publicMaintainedPublic() {}
    public function publicReducedToProtected() {}
    public function publicReducedToPrivate() {}
    protected function protectedMaintainedProtected() {}
    protected function protectedReducedToPrivate() {}
    protected function protectedIncreasedToPublic() {}
    private function privateMaintainedPrivate() {}
    private function privateIncreasedToProtected() {}
    private function privateIncreasedToPublic() {}
}
PHP
            ,
            $astLocator,
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public function publicMaintainedPublic() {}
    protected function publicReducedToProtected() {}
    private function publicReducedToPrivate() {}
    protected function protectedMaintainedProtected() {}
    private function protectedReducedToPrivate() {}
    public function protectedIncreasedToPublic() {}
    private function privateMaintainedPrivate() {}
    protected function privateIncreasedToProtected() {}
    public function privateIncreasedToPublic() {}
}
PHP
            ,
            $astLocator,
        );

        $fromClassReflector = new DefaultReflector($fromLocator);
        $toClassReflector   = new DefaultReflector($toLocator);
        $fromClass          = $fromClassReflector->reflectClass('TheClass');
        $toClass            = $toClassReflector->reflectClass('TheClass');

        $properties = [

            'publicMaintainedPublic'       => [],
            'publicReducedToProtected'     => ['[BC] CHANGED: Method publicReducedToProtected() of class TheClass visibility reduced from public to protected'],
            'publicReducedToPrivate'       => ['[BC] CHANGED: Method publicReducedToPrivate() of class TheClass visibility reduced from public to private'],
            'protectedMaintainedProtected' => [],
            'protectedReducedToPrivate'    => ['[BC] CHANGED: Method protectedReducedToPrivate() of class TheClass visibility reduced from protected to private'],
            'protectedIncreasedToPublic'   => [],
            'privateMaintainedPrivate'     => [],
            'privateIncreasedToProtected'  => [],
            'privateIncreasedToPublic'     => [],
        ];

        return array_combine(
            array_keys($properties),
            array_map(
                static fn (string $methodName, array $errors): array => [
                    $fromClass->getMethod($methodName),
                    $toClass->getMethod($methodName),
                    $errors,
                ],
                array_keys($properties),
                $properties,
            ),
        );
    }
}
