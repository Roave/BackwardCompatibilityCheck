<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\Variance;

use PhpParser\Node\Identifier;
use PhpParser\Node\NullableType;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsContravariant;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeWithReflectorScope;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_map;
use function array_merge;

final class TypeIsContravariantTest extends TestCase
{
    /**
     * @dataProvider checkedTypes
     */
    public function testContravariance(
        TypeWithReflectorScope $type,
        TypeWithReflectorScope $newType,
        bool $expectedToBeContravariant
    ): void {
        self::assertSame(
            $expectedToBeContravariant,
            (new TypeIsContravariant())($type, $newType)
        );
    }

    /**
     * @return array<string, array<int, bool|ReflectionType|null>>
     * @psalm-return array<string, array{0: TypeWithReflectorScope, 1: TypeWithReflectorScope, 2: bool}>
     */
    public function checkedTypes(): array
    {
        $reflector = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

interface Traversable {}
interface Iterator extends Traversable {}
interface AnInterface {}
interface AnotherInterface {}
class AnotherClass implements AnInterface {}
class AnotherClassWithMultipleInterfaces implements AnInterface, AnotherInterface {}
class AClass {}
class BClass extends AClass {}
class CClass extends BClass {}
PHP
            ,
            (new BetterReflection())->astLocator()
        ));

        $types = [
            'no type to no type is contravariant with itself'                          => [
                null,
                null,
                true,
            ],
            'no type to void type is not contravariant'                                => [
                null,
                ReflectionType::createFromNode(new Identifier('void')),
                false,
            ],
            'void type to no type is contravariant'                                    => [
                ReflectionType::createFromNode(new Identifier('void')),
                null,
                true,
            ],
            'void type to scalar type is contravariant'                                => [
                ReflectionType::createFromNode(new Identifier('void')),
                ReflectionType::createFromNode(new Identifier('string')),
                true,
            ],
            'void type to class type is contravariant'                                 => [
                ReflectionType::createFromNode(new Identifier('void')),
                ReflectionType::createFromNode(new Identifier('AClass')),
                true,
            ],
            'scalar type to no type is contravariant'                                  => [
                ReflectionType::createFromNode(new Identifier('string')),
                null,
                true,
            ],
            'no type to scalar type is not contravariant'                              => [
                null,
                ReflectionType::createFromNode(new Identifier('string')),
                false,
            ],
            'class type to no type is contravariant'                                   => [
                ReflectionType::createFromNode(new Identifier('AClass')),
                null,
                true,
            ],
            'no type to class type is not contravariant'                               => [
                ReflectionType::createFromNode(new Identifier('AClass')),
                null,
                true,
            ],
            'iterable to array is not contravariant'                 => [
                ReflectionType::createFromNode(new Identifier('iterable')),
                ReflectionType::createFromNode(new Identifier('array')),
                false,
            ],
            'array to iterable is contravariant'                 => [
                ReflectionType::createFromNode(new Identifier('array')),
                ReflectionType::createFromNode(new Identifier('iterable')),
                true,
            ],
            'iterable to non-iterable class type is not contravariant'                 => [
                ReflectionType::createFromNode(new Identifier('iterable')),
                ReflectionType::createFromNode(new Identifier('AnotherClassWithMultipleInterfaces')),
                false,
            ],
            'iterable to iterable class type is not contravariant'                         => [
                ReflectionType::createFromNode(new Identifier('iterable')),
                ReflectionType::createFromNode(new Identifier('Iterator')),
                false,
            ],
            'non-iterable class to iterable type is not contravariant'                 => [
                ReflectionType::createFromNode(new Identifier('iterable')),
                ReflectionType::createFromNode(new Identifier('AnotherClassWithMultipleInterfaces')),
                false,
            ],
            'iterable class type to iterable is not contravariant'                     => [
                ReflectionType::createFromNode(new Identifier('Iterator')),
                ReflectionType::createFromNode(new Identifier('iterable')),
                false,
            ],
            'object to class type is not contravariant'                                => [
                ReflectionType::createFromNode(new Identifier('object')),
                ReflectionType::createFromNode(new Identifier('AClass')),
                false,
            ],
            'class type to object is contravariant'                                    => [
                ReflectionType::createFromNode(new Identifier('AClass')),
                ReflectionType::createFromNode(new Identifier('object')),
                true,
            ],
            'class type to scalar type is not contravariant'                           => [
                ReflectionType::createFromNode(new Identifier('AClass')),
                ReflectionType::createFromNode(new Identifier('string')),
                false,
            ],
            'scalar type to class type is not contravariant'                           => [
                ReflectionType::createFromNode(new Identifier('string')),
                ReflectionType::createFromNode(new Identifier('AClass')),
                false,
            ],
            'scalar type (string) to different scalar type (int) is not contravariant' => [
                ReflectionType::createFromNode(new Identifier('string')),
                ReflectionType::createFromNode(new Identifier('int')),
                false,
            ],
            'scalar type (int) to different scalar type (float) is not contravariant'  => [
                ReflectionType::createFromNode(new Identifier('int')),
                ReflectionType::createFromNode(new Identifier('float')),
                false,
            ],
            'object type to scalar type is not contravariant'                          => [
                ReflectionType::createFromNode(new Identifier('object')),
                ReflectionType::createFromNode(new Identifier('string')),
                false,
            ],
            'scalar type to object type is not contravariant'                          => [
                ReflectionType::createFromNode(new Identifier('string')),
                ReflectionType::createFromNode(new Identifier('object')),
                false,
            ],
            'class to superclass is contravariant'                                     => [
                ReflectionType::createFromNode(new Identifier('BClass')),
                ReflectionType::createFromNode(new Identifier('AClass')),
                true,
            ],
            'class to subclass is not contravariant'                                   => [
                ReflectionType::createFromNode(new Identifier('BClass')),
                ReflectionType::createFromNode(new Identifier('CClass')),
                false,
            ],
            'class to implemented interface is contravariant'                          => [
                ReflectionType::createFromNode(new Identifier('AnotherClassWithMultipleInterfaces')),
                ReflectionType::createFromNode(new Identifier('AnInterface')),
                true,
            ],
            'class to not implemented interface is not contravariant'                  => [
                ReflectionType::createFromNode(new Identifier('AnotherClassWithMultipleInterfaces')),
                ReflectionType::createFromNode(new Identifier('Traversable')),
                false,
            ],
            'interface to parent interface is contravariant'                           => [
                ReflectionType::createFromNode(new Identifier('Iterator')),
                ReflectionType::createFromNode(new Identifier('Traversable')),
                true,
            ],
            'interface to child interface is contravariant'                            => [
                ReflectionType::createFromNode(new Identifier('Traversable')),
                ReflectionType::createFromNode(new Identifier('Iterator')),
                false,
            ],
        ];

        return array_map(
            static fn (array $types): array => [
                new TypeWithReflectorScope($types[0], $reflector),
                new TypeWithReflectorScope($types[1], $reflector),
                $types[2],
            ],
            $types
        );
    }

    /**
     * @dataProvider existingTypes
     */
    public function testContravarianceConsidersSameTypeAlwaysContravariant(TypeWithReflectorScope $type): void
    {
        self::assertTrue(
            (new TypeIsContravariant())($type, $type)
        );
    }

    /** @return TypeWithReflectorScope[][] */
    public function existingTypes(): array
    {
        $reflector = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

interface Traversable {}
class AClass {}
PHP
            ,
            (new BetterReflection())->astLocator()
        ));

        return array_merge(
            [[new TypeWithReflectorScope(null, $reflector)]],
            array_merge(...array_map(
                static function (string $type) use ($reflector): array {
                    return [
                        [new TypeWithReflectorScope(ReflectionType::createFromNode(new Identifier($type)), $reflector)],
                        [new TypeWithReflectorScope(ReflectionType::createFromNode(new NullableType(new Identifier($type))), $reflector)],
                    ];
                },
                [
                    'int',
                    'string',
                    'float',
                    'bool',
                    'array',
                    'iterable',
                    'callable',
                    'Traversable',
                    'AClass',
                ]
            ))
        );
    }

    /**
     * @dataProvider existingNullableTypeStrings
     */
    public function testContravarianceConsidersNullability(string $type): void
    {
        $reflector   = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

interface Traversable {}
class AClass {}
PHP
            ,
            (new BetterReflection())->astLocator()
        ));
        $nullable    = new TypeWithReflectorScope(ReflectionType::createFromNode(new NullableType(new Identifier($type))), $reflector);
        $notNullable = new TypeWithReflectorScope(ReflectionType::createFromNode(new Identifier($type)), $reflector);

        $isContravariant = new TypeIsContravariant();

        self::assertFalse($isContravariant($nullable, $notNullable));
        self::assertTrue($isContravariant($notNullable, $nullable));
    }

    /** @return string[][] */
    public function existingNullableTypeStrings(): array
    {
        return [
            ['int'],
            ['string'],
            ['float'],
            ['bool'],
            ['array'],
            ['iterable'],
            ['callable'],
            ['Traversable'],
            ['AClass'],
        ];
    }
}
