<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Location::class)]
final class LocationTest
{
    public function shortFileStripsProjectPrefix(): void
    {
        $location = new Location(__FILE__, 1);

        Assert::same($location->shortFile, 'tests/Dic/LocationTest.php');
    }

    public function shortFileKeepsPathOutsidePrefix(): void
    {
        $location = new Location('/outside/the/project/Foo.php', 1);

        Assert::same($location->shortFile, '/outside/the/project/Foo.php');
    }

    public function stringableStripsProjectPrefix(): void
    {
        $location = new Location(__FILE__, 42);

        Assert::same((string) $location, 'tests/Dic/LocationTest.php:42');
    }

    public function stringableKeepsPathOutsidePrefix(): void
    {
        $location = new Location('/outside/the/project/Foo.php', 42);

        Assert::same((string) $location, '/outside/the/project/Foo.php:42');
    }
}
