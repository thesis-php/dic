<?php

declare(strict_types=1);

namespace Project;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Project\HttpServer\Server;
use Psr\Log\LoggerInterface;
use Thesis\DIC;

final class App
{
    public function __invoke(DIC $dic): Server
    {
        $dic->bindObject(
            object: new Logger(
                name: 'message_bus',
                handlers: [new StreamHandler(STDOUT)],
                processors: [new PsrLogMessageProcessor()],
            ),
            class: LoggerInterface::class,
        );

        $dic->require(new Authentication\Component());

        return $dic->require(new HttpServer\Component());
    }
}
