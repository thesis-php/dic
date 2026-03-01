<?php

declare(strict_types=1);

namespace Project\Thesis\HttpServerModule;

use Amp\Http\HttpStatus;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Wilaak\Http\RadixRouter;

final readonly class Router implements RequestHandler
{
    private RadixRouter $router;

    /**
     * @param list<Endpoint> $endpoints
     */
    public function __construct(
        array $endpoints = [],
        private ErrorHandler $errorHandler = new DefaultErrorHandler(),
    ) {
        $this->router = new RadixRouter();

        foreach ($endpoints as $endpoint) {
            $this->router->add(
                methods: $endpoint->route->methods,
                pattern: $endpoint->route->path,
                handler: $endpoint->handler,
            );
        }
    }

    public function handleRequest(Request $request): Response
    {
        $result = $this->router->lookup(
            method: $request->getMethod(),
            path: rawurldecode($request->getUri()->getPath()),
        );

        if ($result['code'] === 200) {
            $handler = $result['handler'] ?? null;
            \assert($handler instanceof RequestHandler);

            return $handler->handleRequest($request);
        }

        if ($result['code'] === 404) {
            return $this->errorHandler->handleError(
                status: HttpStatus::NOT_FOUND,
                reason: HttpStatus::getReason(HttpStatus::NOT_FOUND),
                request: $request,
            );
        }

        if ($result['code'] === 405) {
            $response = $this->errorHandler->handleError(
                status: HttpStatus::METHOD_NOT_ALLOWED,
                reason: HttpStatus::getReason(HttpStatus::METHOD_NOT_ALLOWED),
                request: $request,
            );
            $response->setHeader('Allow', implode(', ', $result['allowed_methods'] ?? []));

            return $response;
        }

        return $this->errorHandler->handleError(
            status: $result['code'],
            request: $request,
        );
    }
}
