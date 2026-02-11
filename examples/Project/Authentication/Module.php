<?php

declare(strict_types=1);

namespace Project\Authentication;

use Thesis\DIC;

final readonly class Module
{
    #[DIC\InheritAutowiring]
    public function __invoke(DIC $dic): void
    {
        $dic->register($dic->new(Authenticate::class));
    }
}
