<?php

declare(strict_types=1);

namespace Project;

use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Project\HttpServer\RouterComponent;
use Project\Logger\LoggerFactory;
use Thesis\DIC;
use Thesis\DIC\Ref;

final class App
{
    /**
     * @return Ref<array{SocketHttpServer, Router}>
     */
    public function __invoke(DIC $dic): Ref
    {
        $logger = $dic
            ->factory(LoggerFactory::stdOut(...))
            ->bind();

        /** @phpstan-ignore argument.type */
        $server = $dic->factory(SocketHttpServer::createForDirectAccess(...));

        $router = $dic->require(new RouterComponent($server, $logger));

        $dic->require(new Authentication\Module());

        return $dic
            /** @phpstan-ignore argument.type */
            ->factory(static fn(SocketHttpServer $server, Router $router) => [$server, $router])
            ->args([$server, $router]);
    }
}
