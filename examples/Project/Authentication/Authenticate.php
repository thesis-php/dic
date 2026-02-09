<?php

declare(strict_types=1);

namespace Project\Authentication;

use Project\HttpServer\Request;
use Project\HttpServer\Response;

final readonly class Authenticate
{
    public function __invoke(Request $request): Response
    {
        dump($this);

        return new Response();
    }
}
