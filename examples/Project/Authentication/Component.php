<?php

declare(strict_types=1);

namespace Project\Authentication;

use Project\HttpServer\AsController;
use Thesis\DIC;

final readonly class Component
{
    public function __invoke(DIC $dic): void
    {
        $dic->object(Authenticate::class, tags: [new AsController()]);
    }
}
