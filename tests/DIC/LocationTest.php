<?php

declare(strict_types=1);

namespace Thesis\DIC;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Location::class)]
final class LocationTest extends TestCase
{
    public function testCurrent(): void
    {
        $location = Location::fromTrace();

        self::assertSame(__FILE__, $location->file);
        self::assertSame(__LINE__ - 3, $location->line);
    }

    public function testCaller(): void
    {
        (static function (): void {
            $location = Location::caller();

            self::assertSame(__FILE__, $location->file);
            self::assertSame(__LINE__ + 1, $location->line);
        })(); // the expected line
    }

    public function testFromTraceWithIndex(): void
    {
        $fn = static fn() => Location::fromTrace(1);

        $location = $fn();

        self::assertSame(__FILE__, $location->file);
        self::assertSame(__LINE__ - 3, $location->line);
    }

    public function testCallerWithIndex(): void
    {
        $inner = static fn() => Location::caller(1);
        $outer = static fn() => $inner(); // the expected line

        $location = $outer();

        self::assertSame(__FILE__, $location->file);
        self::assertSame(__LINE__ - 3, $location->line);
    }

    public function testEval(): void
    {
        $location = eval(
            // the expected line is below
            <<<'PHP'
                // line 1
                // line 2
                
                return \Thesis\DIC\Location::fromTrace();
                PHP
        );

        self::assertInstanceOf(Location::class, $location);
        self::assertSame(__LINE__ - 8, $location->line);
        self::assertSame(__FILE__, $location->file);
    }

    public function testToString(): void
    {
        $location = new Location('a', 2);

        $string = (string) $location;

        self::assertSame('a:2', $string);
    }

    public function testItThrowsOnInvalidIndex(): void
    {
        $this->expectExceptionObject(new \LogicException('Invalid trace index'));

        Location::fromTrace(1_000);
    }
}
