<?php

declare(strict_types=1);

namespace Project\HttpServer;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function Typhoon\Formatter\formatFunction;

/**
 * @phpstan-type Action = callable(Request): Response
 */
final readonly class Server
{
    /**
     * @param array<string, Action>|\ArrayAccess<string, Action> $actions
     */
    public function __construct(
        private array|\ArrayAccess $actions = [],
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function handle(Request $request): Response
    {
        $action = $this->actions[$request->path] ?? null;

        if ($action === null) {
            $this->logger->debug('No action for path {path}', [
                'path' => $request->path,
            ]);

            return new Response(404);
        }

        $this->logger->debug('Matched action {action} for path {path}', [
            'action' => formatFunction($action),
            'path' => $request->path,
        ]);

        $response = $action($request);

        $this->logger->debug('Action {action} responded {status}', [
            'action' => formatFunction($action),
            'status' => $response->status,
            'body' => $response->body,
        ]);

        return $response;
    }
}
