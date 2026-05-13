<?php

declare(strict_types=1);

namespace Thesis;

use Testo\Assert;
use Testo\Test;
use Thesis\Dic\Ref;

final readonly class DicTest
{
    #[Test]
    public function value(): void
    {
        $value = self::assemble(static fn(Dic $dic) => $dic->value(1));

        Assert::same($value, 1);
    }

    #[Test]
    public function valueRef(): void
    {
        $value = self::assemble(static function (Dic $dic) {
            $ref = $dic->value(1);

            return $dic->value($ref);
        });

        Assert::same($value, 1);
    }

    #[Test]
    public function valueArrayRef(): void
    {
        $value = self::assemble(static function (Dic $dic) {
            $ref1 = $dic->value(1);
            $ref2 = $dic->value(2);

            return $dic->value([$ref1, $ref2]);
        });

        Assert::same($value, [1, 2]);
    }

    /**
     * @template T
     * @param callable(Dic): Ref<T> $module
     * @return T
     */
    private static function assemble(callable $module): mixed
    {
        return Dic::run($module, static fn(mixed $value) => $value);
    }
}
