<?php

declare(strict_types=1);

namespace Project\Thesis\HttpServerModule;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\DefaultExceptionHandler;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\ExceptionHandler;
use Amp\Http\Server\SocketHttpServer;
use Psr\Log\LoggerInterface;
use Thesis\DIC;
use Thesis\DIC\Ref;
use Thesis\DIC\TaggedRef;
use Thesis\DIC\Tags;
use function Typhoon\Type\objectT;

final readonly class Module
{
    /**
     * @param Ref<LoggerInterface>|LoggerInterface $logger
     * @param Ref<ErrorHandler>|ErrorHandler $errorHandler
     * @param null|Ref<ExceptionHandler>|ExceptionHandler $exceptionHandler
     * @param ?positive-int $concurrencyLimit
     * @param list<non-empty-string> $allowedMethods
     */
    public function __construct(
        private Direct|Proxy $mode,
        private Ref|LoggerInterface $logger,
        private Ref|ErrorHandler $errorHandler = new DefaultErrorHandler(),
        private null|Ref|ExceptionHandler $exceptionHandler = null,
        private bool $enableCompression = true,
        private ?int $concurrencyLimit = 1_000,
        private array $allowedMethods = METHODS,
    ) {}

    /**
     * @return Ref<Server>
     */
    public function __invoke(DIC $dic): Ref
    {
        $dic->bind(objectT(LoggerInterface::class), $this->logger);
        $dic->bind(objectT(ErrorHandler::class), $this->errorHandler);

        $router = $dic->object(Router::class);

        $dic->onResolveTags(static function (Tags $tags) use ($dic, $router): void {
            $router->arg('endpoints', self::endpoints($dic, $tags));
        });

        return $dic
            ->object(Server::class)
            ->args([
                'httpServer' => $this->socketHttpServer($dic),
                'requestHandler' => $router,
            ]);
    }

    /**
     * @return list<Ref<Endpoint>>
     */
    private static function endpoints(DIC $dic, Tags $tags): array
    {
        $middleware = $dic->taggedList(
            AsMiddleware::class,
            static fn(TaggedRef $a, TaggedRef $b) => $b->tag->priority <=> $a->tag->priority,
        );

        return array_map(
            static fn(TaggedRef $tr) => $dic
                ->object(Endpoint::class)
                ->args([
                    'route' => $tr->tag,
                    'handler' => $dic
                        ->object(DICPipeline::class)
                        ->args([
                            'scopedHandler' => $dic->scope($tr->ref),
                            'middleware' => $middleware,
                        ]),
                ]),
            $tags->tagged(Route::class),
        );
    }

    /**
     * @return Ref<SocketHttpServer>
     */
    private function socketHttpServer(DIC $dic): Ref
    {
        if ($this->mode instanceof Direct) {
            $httpServer = $dic
                /** @phpstan-ignore argument.type */
                ->factory(SocketHttpServer::createForDirectAccess(...))
                ->args([
                    'connectionLimit' => $this->mode->connectionLimit,
                    'connectionLimitPerIp' => $this->mode->connectionLimitPerIp,
                ]);
        } else {
            $httpServer = $dic
                /** @phpstan-ignore argument.type */
                ->factory(SocketHttpServer::createForBehindProxy(...))
                ->args([
                    'headerType' => $this->mode->headerType,
                    'trustedProxies' => $this->mode->trustedProxies,
                ]);
        }

        return $httpServer
            ->arg('exceptionHandler', $this->exceptionHandler ?? $dic->object(DefaultExceptionHandler::class))
            ->arg('enableCompression', $this->enableCompression)
            ->arg('concurrencyLimit', $this->concurrencyLimit)
            ->arg('allowedMethods', $this->allowedMethods);
    }
}
