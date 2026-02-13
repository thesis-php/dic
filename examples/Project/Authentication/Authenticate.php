<?php

declare(strict_types=1);

namespace Project\Authentication;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

final readonly class Authenticate
{
    public function __invoke(Request $request): Response
    {
        return new Response(body: 'Authenticated!');
    }
}
