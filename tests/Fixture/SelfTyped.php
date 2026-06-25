<?php

declare(strict_types=1);

namespace Thesis\Fixture;

final class SelfTyped
{
    public function withSelf(self $other): self
    {
        return $other;
    }
}
