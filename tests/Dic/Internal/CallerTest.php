<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\Location;

#[Test]
#[Covers('Thesis\Dic\Internal\caller')]
final class CallerTest
{
    public function capturesTheCallSiteOutsideTheLibrary(): void
    {
        $location = caller();

        Assert::same($location->file, __FILE__);
        Assert::same($location->line, __LINE__ - 3);
    }

    public function normalizesEvalSource(): void
    {
        $location = eval(
            // the expected line is below
            <<<'PHP'
                $fn = static fn() => \Thesis\Dic\Internal\caller();

                return $fn();
                PHP
        );

        Assert::instanceOf($location, Location::class);
        Assert::same($location->file, __FILE__);
        Assert::same($location->line, __LINE__ - 8);
    }
}
