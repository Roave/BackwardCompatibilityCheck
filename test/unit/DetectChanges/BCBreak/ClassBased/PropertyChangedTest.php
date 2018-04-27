<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\DetectChanges\BCBreak\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\DetectChanges\BCBreak\ClassBased\PropertyChanged;
use Roave\ApiCompare\DetectChanges\BCBreak\PropertyBased\PropertyBased;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\ApiCompare\DetectChanges\BCBreak\ClassBased\PropertyChanged
 */
final class PropertyChangedTest extends TestCase
{
    public function testWillDetectChangesInProperties() : void
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public $a;
    protected $b;
    private $c;
    public static $d;
    public $G;
}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    protected $b;
    public static $d;
    public $e;
    public $f;
    public $g;
}
PHP
            ,
            $astLocator
        );

        $comparator = $this->createMock(PropertyBased::class);

        $comparator
            ->expects(self::exactly(2))
            ->method('__invoke')
            ->willReturnCallback(function (ReflectionProperty $from, ReflectionProperty $to) : Changes {
                $propertyName = $from->getName();

                self::assertSame($propertyName, $to->getName());

                return Changes::fromList(Change::added($propertyName, true));
            });

        self::assertEquals(
            Changes::fromList(
                Change::added('b', true),
                Change::added('d', true)
            ),
            (new PropertyChanged($comparator))->__invoke(
                (new ClassReflector($fromLocator))->reflect('TheClass'),
                (new ClassReflector($toLocator))->reflect('TheClass')
            )
        );
    }
}
