<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\Variance;

use PhpParser\Node\Identifier;
use PhpParser\Node\NullableType;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsCovariant;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeWithReflectorScope;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_map;
use function array_merge;

final class TypeIsCovariantTest extends TestCase
{
    /**
     * @dataProvider checkedTypes
     */
    public function testCovariance(
        TypeWithReflectorScope $type,
        TypeWithReflectorScope $newType,
        bool $expectedToBeContravariant
    ): void {
        self::assertSame(
            $expectedToBeContravariant,
            (new TypeIsCovariant())($type, $newType)
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
            'no type to void type is covariant'                    => [
                null,
                ReflectionType::createFromNode(new Identifier('void')),
                true,
            ],
            'void type to no type is not covariant'                => [
                ReflectionType::createFromNode(new Identifier('void')),
                null,
                false,
            ],
            'void type to scalar type is not covariant'            => [
                ReflectionType::createFromNode(new Identifier('void')),
                ReflectionType::createFromNode(new Identifier('string')),
                false,
            ],
            'void type to class type is covariant'                 => [
                ReflectionType::createFromNode(new Identifier('void')),
                ReflectionType::createFromNode(new Identifier('AClass')),
                false,
            ],
            'scalar type to no type is not covariant'              => [
                ReflectionType::createFromNode(new Identifier('string')),
                null,
                false,
            ],
            'no type to scalar type is covariant'                  => [
                null,
                ReflectionType::createFromNode(new Identifier('string')),
                true,
            ],
            'class type to no type is not covariant'               => [
                ReflectionType::createFromNode(new Identifier('AClass')),
                null,
                false,
            ],
            'no type to class type is not contravariant'           => [
                ReflectionType::createFromNode(new Identifier('AClass')),
                null,
                false,
            ],
            'iterable to array is covariant' => [
                ReflectionType::createFromNode(new Identifier('iterable')),
                ReflectionType::createFromNode(new Identifier('array')),
                true,
            ],
            'iterable to scalar is not covariant' => [
                ReflectionType::createFromNode(new Identifier('iterable')),
                ReflectionType::createFromNode(new Identifier('int')),
                false,
            ],
            'scalar to iterable is not covariant' => [
                ReflectionType::createFromNode(new Identifier('iterable')),
                ReflectionType::createFromNode(new Identifier('int')),
                false,
            ],
            'array to iterable is not covariant'         => [
                ReflectionType::createFromNode(new Identifier('array')),
                ReflectionType::createFromNode(new Identifier('iterable')),
                false,
            ],
            'iterable to non-iterable class type is not covariant' => [
                ReflectionType::createFromNode(new Identifier('iterable')),
                ReflectionType::createFromNode(new Identifier('AnotherClassWithMultipleInterfaces')),
                false,
            ],
            'iterable to iterable class type is covariant'         => [
                ReflectionType::createFromNode(new Identifier('iterable')),
                ReflectionType::createFromNode(new Identifier('Iterator')),
                true,
            ],
            'non-iterable class to iterable type is not covariant' => [
                ReflectionType::createFromNode(new Identifier('iterable')),
                ReflectionType::createFromNode(new Identifier('AnotherClassWithMultipleInterfaces')),
                false,
            ],
            'iterable class type to iterable is not covariant'     => [
                ReflectionType::createFromNode(new Identifier('Iterator')),
                ReflectionType::createFromNode(new Identifier('iterable')),
                false,
            ],
            'object to class type is covariant'                    => [
                ReflectionType::createFromNode(new Identifier('object')),
                ReflectionType::createFromNode(new Identifier('AClass')),
                true,
            ],
            'class type to object is not covariant'                => [
                ReflectionType::createFromNode(new Identifier('AClass')),
                ReflectionType::createFromNode(new Identifier('object')),
                false,
            ],

            'class type to scalar type is not covariant'                           => [
                ReflectionType::createFromNode(new Identifier('AClass')),
                ReflectionType::createFromNode(new Identifier('string')),
                false,
            ],
            'scalar type to class type is not covariant'                           => [
                ReflectionType::createFromNode(new Identifier('string')),
                ReflectionType::createFromNode(new Identifier('AClass')),
                false,
            ],
            'scalar type (string) to different scalar type (int) is not covariant' => [
                ReflectionType::createFromNode(new Identifier('string')),
                ReflectionType::createFromNode(new Identifier('int')),
                false,
            ],
            'scalar type (int) to different scalar type (float) is not covariant'  => [
                ReflectionType::createFromNode(new Identifier('int')),
                ReflectionType::createFromNode(new Identifier('float')),
                false,
            ],
            'object type to scalar type is not contravariant'                      => [
                ReflectionType::createFromNode(new Identifier('object')),
                ReflectionType::createFromNode(new Identifier('string')),
                false,
            ],
            'scalar type to object type is not covariant'                          => [
                ReflectionType::createFromNode(new Identifier('string')),
                ReflectionType::createFromNode(new Identifier('object')),
                false,
            ],
            'class to superclass is not covariant'                                 => [
                ReflectionType::createFromNode(new Identifier('BClass')),
                ReflectionType::createFromNode(new Identifier('AClass')),
                false,
            ],
            'class to subclass is covariant'                                       => [
                ReflectionType::createFromNode(new Identifier('BClass')),
                ReflectionType::createFromNode(new Identifier('CClass')),
                true,
            ],
            'class to implemented interface is not covariant'                      => [
                ReflectionType::createFromNode(new Identifier('AnotherClassWithMultipleInterfaces')),
                ReflectionType::createFromNode(new Identifier('AnInterface')),
                false,
            ],
            'interface to implementing class is covariant'                         => [
                ReflectionType::createFromNode(new Identifier('AnInterface')),
                ReflectionType::createFromNode(new Identifier('AnotherClassWithMultipleInterfaces')),
                true,
            ],
            'class to not implemented interface is not covariant'                  => [
                ReflectionType::createFromNode(new Identifier('AnotherClassWithMultipleInterfaces')),
                ReflectionType::createFromNode(new Identifier('Traversable')),
                false,
            ],
            'interface to parent interface is not covariant'                       => [
                ReflectionType::createFromNode(new Identifier('Iterator')),
                ReflectionType::createFromNode(new Identifier('Traversable')),
                false,
            ],
            'interface to child interface is covariant'                            => [
                ReflectionType::createFromNode(new Identifier('Traversable')),
                ReflectionType::createFromNode(new Identifier('Iterator')),
                true,
            ],
        ];

        return array_map(
            static fn (array $types): array => [
                new TypeWithReflectorScope($types[0], $reflector),
                new TypeWithReflectorScope($types[1], $reflector),
                $types[2]
            ],
            $types
        );
    }

    /**
     * @dataProvider existingTypes
     */
    public function testCovarianceConsidersSameTypeAlwaysCovariant(TypeWithReflectorScope $type): void
    {
        self::assertTrue(
            (new TypeIsCovariant())($type, $type)
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
    public function testCovarianceConsidersNullability(string $type): void
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

        $isCovariant = new TypeIsCovariant();

        self::assertTrue($isCovariant($nullable, $notNullable));
        self::assertFalse($isCovariant($notNullable, $nullable));
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
