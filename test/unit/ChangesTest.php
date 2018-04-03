<?php
declare(strict_types=1);

namespace RoaveTest\ApiCompare;

use InvalidArgumentException;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Roave\ApiCompare\Changes
 */
final class ChangesTest extends TestCase
{
    public function testFromArray() : void
    {
        $change = Change::added('added', true);

        self::assertSame(
            [$change],
            iterator_to_array(Changes::fromArray([$change]))
        );
    }

    /**
     * @return mixed[]
     * @throws \Exception
     */
    public function invalidChangeProvider() : array
    {
        return [
            [random_int(1, 100)],
            [random_int(1, 100) / 1000],
            [uniqid('string', true)],
            [random_bytes(24)],
            [[]],
            [null],
            [true],
            [false],
            [new \stdClass()],
        ];
    }

    /**
     * @param mixed $invalidChange
     * @dataProvider invalidChangeProvider
     */
    public function testFromArrayThowsExceptionWhenInvalidChangePassed($invalidChange) : void
    {
        $this->expectException(InvalidArgumentException::class);
        Changes::fromArray([$invalidChange]);
    }

    public function testWithAddedChange() : void
    {
        $change = Change::added('added', true);

        self::assertSame(
            [$change],
            iterator_to_array(Changes::new()->withAddedChange($change))
        );
    }
}
