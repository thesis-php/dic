<?php

declare(strict_types=1);

namespace Project;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Project\HttpServer\Server;
use Psr\Log\LoggerInterface;
use Thesis\DIC;
use function Typhoon\Type\objectT;

final class App
{
    public function __invoke(DIC $dic): Server
    {
        $dic
            ->register(new Logger(
                name: 'app',
                handlers: [new StreamHandler(STDOUT)],
                processors: [new PsrLogMessageProcessor()],
            ))
            ->bind(objectT(LoggerInterface::class));

        $dic->require(new Authentication\Module()); // @phpstan-ignore argument.type

        return $dic->require(new HttpServer\Module()); // @phpstan-ignore argument.type
    }
}
