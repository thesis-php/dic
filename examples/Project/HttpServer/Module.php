<?php

declare(strict_types=1);

namespace Project\HttpServer;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Thesis\DIC;
use Thesis\DIC\TaggedValue;

final readonly class Module
{
    public function __invoke(DIC $dic, LoggerInterface $logger = new NullLogger()): Server
    {
        return $dic->new(Server::class, [
            'actions' => $dic->tagged(
                tag: Route::class,
                key: static fn(TaggedValue $tv) => $tv->tag->path,
            ),
            'logger' => $logger,
        ]);
    }
}
