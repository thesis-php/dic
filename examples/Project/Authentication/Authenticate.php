<?php

declare(strict_types=1);

namespace Project\Authentication;

use Project\HttpServer\Request;
use Project\HttpServer\Response;
use Project\HttpServer\Route;
use Psr\Log\LoggerInterface;

final readonly class Authenticate
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    #[Route('/authenticate')]
    public function __invoke(Request $request): Response
    {
        $this->logger->debug('Authenticating...');

        return new Response(body: 'Authenticated!');
    }
}
