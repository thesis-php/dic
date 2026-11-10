<?php

declare(strict_types=1);

namespace Thesis;

use Thesis\Dic\ClosureModule;
use Thesis\Fixture\Counter;
use function PHPStan\Testing\assertType;

/**
 * Checked by PHPStan only, never executed: pins the inferred types of the root module overloads.
 */
final readonly class DicTypes
{
    public function buildInfersTypeFromCallable(): void
    {
        assertType(Counter::class, Dic::build(static fn(Dic $dic) => $dic->object(Counter::class)));
        assertType("'plain'", Dic::build(static fn(Dic $dic) => 'plain'));
    }

    public function buildInfersTypeFromModule(): void
    {
        assertType(
            Counter::class,
            Dic::build(new ClosureModule(static fn(Dic $dic) => $dic->object(Counter::class))),
        );
    }

    public function runInfersTypesFromCallable(): void
    {
        assertType('int', Dic::run(
            static fn(Dic $dic) => $dic->object(Counter::class),
            static function (Counter $counter): int {
                assertType(Counter::class, $counter);

                return $counter->value;
            },
        ));
    }
}
