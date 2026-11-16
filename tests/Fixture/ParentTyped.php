<?php

declare(strict_types=1);

namespace Thesis\Fixture;

final class ParentTyped extends Base
{
    public function withParent(parent $other): parent
    {
        return $other;
    }

    public function withNullableParent(?parent $other): ?parent
    {
        return $other;
    }

    /**
     * @return \Closure(?Base): string
     */
    public static function describeNullableParent(): \Closure
    {
        return static fn(?parent $other): string => $other === null ? 'null' : $other::class;
    }
}
