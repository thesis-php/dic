<?php

declare(strict_types=1);

namespace Project\HttpServer;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Thesis\DIC;

final readonly class Component
{
    public function __invoke(DIC $dic, LoggerInterface $logger = new NullLogger()): Server
    {
        $dic->bindObject($logger, LoggerInterface::class);

        return $dic->object(Server::class, [
            'controllers' => $dic->taggedIterator(AsController::class),
        ]);
    }
}
