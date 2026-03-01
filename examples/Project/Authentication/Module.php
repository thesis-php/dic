<?php

declare(strict_types=1);

namespace Project\Authentication;

use Thesis\DIC;

final readonly class Module
{
    public function __invoke(DIC $dic): void
    {
        $dic->inheritAutowiring();

        $dic->object(Authenticate::class);
    }
}
