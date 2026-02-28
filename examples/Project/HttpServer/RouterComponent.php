<?php

declare(strict_types=1);

namespace Project\HttpServer;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Thesis\DIC;
use Thesis\DIC\Ref;
use Thesis\DIC\TaggedRef;

final readonly class RouterComponent
{
    /**
     * @param Ref<HttpServer> $httpServer
     * @param Ref<LoggerInterface> $logger
     */
    public function __construct(
        private Ref $httpServer,
        private Ref $logger = new DIC\Value(new NullLogger()),
    ) {}

    /**
     * @return Ref<Router>
     */
    public function __invoke(DIC $dic): Ref
    {
        $router = $dic
            /** @phpstan-ignore argument.type */
            ->factory(self::router(...))
            ->args([
                $this->httpServer,
                $this->logger,
            ]);

        $dic->onResolveTags(static function (DIC\Tags $tags) use ($router): void {
            $router->arg('actions', array_map(
                static fn(TaggedRef $tr) => [$tr->ref, $tr->tag],
                $tags->taggedBy(Route::class),
            ));
        });

        return $router;
    }

    /**
     * @param list<array{callable(Request): Response, Route}> $actions
     */
    private static function router(HttpServer $httpServer, LoggerInterface $logger, array $actions): Router
    {
        $router = new Router(
            httpServer: $httpServer,
            logger: $logger,
            errorHandler: new DefaultErrorHandler(),
        );

        foreach ($actions as [$action, $route]) {
            $router->addRoute(
                method: $route->method,
                uri: $route->path,
                requestHandler: new ClosureRequestHandler($action(...)),
            );
        }

        return $router;
    }
}
