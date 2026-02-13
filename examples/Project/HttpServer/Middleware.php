<?php

declare(strict_types=1);

namespace Project\HttpServer;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

interface Middleware
{
    public function handleRequest(Request $request, Pipeline $pipeline): Response;
}
