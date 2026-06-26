<?php

declare(strict_types=1);

namespace Thesis\Fixture;

final class ParentTyped extends Base
{
    public function withParent(parent $other): parent
    {
        return $other;
    }
}
