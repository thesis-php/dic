<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Location::class)]
final class LocationTest extends TestCase
{
    public function testCurrent(): void
    {
        $location = Location::current();

        self::assertSame(__FILE__, $location->file);
        self::assertSame(__LINE__ - 3, $location->line);
    }

    public function testCaller(): void
    {
        $location = (static fn(): Location => Location::caller())();

        self::assertSame(__FILE__, $location->file);
        self::assertSame(__LINE__ - 3, $location->line);
    }
}
