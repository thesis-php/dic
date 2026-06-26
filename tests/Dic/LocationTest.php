<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Covers(Location::class)]
final class LocationTest
{
    #[Test]
    public function caller(): void
    {
        (static function (): void {
            $location = Location::caller();

            Assert::same($location->file, __FILE__);
            Assert::same($location->line, __LINE__ + 1);
        })(); // the expected line
    }

    #[Test]
    public function callerInsideEval(): void
    {
        $location = eval(
            // the expected line is below
            <<<'PHP'
                $fn = static fn() => \Thesis\Dic\Location::caller();

                return $fn();
                PHP
        );

        Assert::instanceOf($location, Location::class);
        Assert::same($location->file, __FILE__);
        Assert::same($location->line, __LINE__ - 8);
    }

    #[Test]
    public function shortFileStripsProjectPrefix(): void
    {
        $location = new Location(__FILE__, 1);

        Assert::same($location->shortFile, 'tests/Dic/LocationTest.php');
    }

    #[Test]
    public function shortFileKeepsPathOutsidePrefix(): void
    {
        $location = new Location('/outside/the/project/Foo.php', 1);

        Assert::same($location->shortFile, '/outside/the/project/Foo.php');
    }

    #[Test]
    public function stringableStripsProjectPrefix(): void
    {
        $location = new Location(__FILE__, 42);

        Assert::same((string) $location, 'tests/Dic/LocationTest.php:42');
    }

    #[Test]
    public function stringableKeepsPathOutsidePrefix(): void
    {
        $location = new Location('/outside/the/project/Foo.php', 42);

        Assert::same((string) $location, '/outside/the/project/Foo.php:42');
    }
}
