<?php

declare(strict_types=1);

namespace Project\Thesis\HttpServerModule;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServerStatus;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\BindContext;
use Amp\Socket\SocketAddress;

final class Server
{
    public HttpServerStatus $status { get => $this->httpServer->getStatus(); }

    public function __construct(
        private readonly SocketHttpServer $httpServer,
        private readonly RequestHandler $requestHandler,
        private readonly ErrorHandler $errorHandler = new DefaultErrorHandler(),
    ) {}

    public function expose(SocketAddress|string $socketAddress, ?BindContext $bindContext = null): void
    {
        $this->httpServer->expose($socketAddress, $bindContext);
    }

    public function start(): void
    {
        $this->httpServer->start($this->requestHandler, $this->errorHandler);
    }

    public function stop(): void
    {
        $this->httpServer->stop();
    }
}
