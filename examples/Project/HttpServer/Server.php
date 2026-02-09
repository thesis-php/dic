<?php

declare(strict_types=1);

namespace Project\HttpServer;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function Typhoon\Formatter\formatFunction;

/**
 * @phpstan-type Controller = callable(Request): Response
 */
final readonly class Server
{
    /**
     * @param iterable<Controller> $controllers
     */
    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
        private iterable $controllers = [],
    ) {}

    public function run(): void
    {
        $this->logger->debug('Server started');
        $this->logger->debug('Just for demo purposes I will invoke every controller');

        foreach ($this->controllers as $controller) {
            $this->logger->debug('Invoking controller ' . formatFunction($controller));

            $controller(new Request());
        }
    }
}
