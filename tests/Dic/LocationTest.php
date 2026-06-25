<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;

#[Covers(Location::class)]
final class LocationTest
{
    #[Test]
    public function fromTrace(): void
    {
        $location = Location::fromTrace();

        Assert::same($location->file, __FILE__);
        Assert::same($location->line, __LINE__ - 3);
    }

    #[Test]
    public function fromTraceWithIndex(): void
    {
        $fn = static fn() => Location::fromTrace(1);

        $location = $fn();

        Assert::same($location->file, __FILE__);
        Assert::same($location->line, __LINE__ - 3);
    }

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
    public function callerWithIndex(): void
    {
        $inner = static fn() => Location::caller(1);
        $outer = static fn() => $inner(); // the expected line

        $location = $outer();

        Assert::same($location->file, __FILE__);
        Assert::same($location->line, __LINE__ - 3);
    }

    #[Test]
    public function eval(): void
    {
        $location = eval(
            // the expected line is below
            <<<'PHP'
                // line 1
                // line 2
                
                return \Thesis\Dic\Location::fromTrace();
                PHP
        );

        Assert::instanceOf($location, Location::class);
        Assert::same($location->line, __LINE__ - 8);
        Assert::same($location->file, __FILE__);
    }

    #[Test]
    public function throwsOnInvalidIndex(): void
    {
        Expect::exception(\OutOfRangeException::class)
            ->withMessage('Invalid trace index');

        Location::fromTrace(1_000);
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
