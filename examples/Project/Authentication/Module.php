<?php

declare(strict_types=1);

namespace Project\Authentication;

use Project\HttpServer\Route;
use Thesis\DIC;

final readonly class Module
{
    public function __invoke(DIC $dic): void
    {
        $dic->object(Authenticate::class)
            ->tag(new Route('GET', '/authenticate'));
    }
}
