<?php

declare(strict_types=1);

namespace Autoload
{
    require_once __DIR__ . '/../vendor/autoload.php';
}

namespace MessageBus
{
    use Psr\Log\LoggerInterface;
    use Psr\Log\NullLogger;
    use Thesis\DIC;

    interface Handler {}

    final readonly class MessageBus
    {
        /**
         * @param list<Handler> $handlers
         */
        public function __construct(
            private array $handlers = [],
            private LoggerInterface $logger = new NullLogger(),
        ) {}

        public function run(): void
        {
            $this->logger->debug('Hello! Here are my handlers:');

            dump($this->handlers);
        }
    }

    /**
     * @implements DIC\Tag<Handler>
     */
    enum AsHandler implements DIC\Tag
    {
        case Tag;
    }

    final readonly class Component
    {
        /**
         * @param DIC\Service<LoggerInterface> $logger
         */
        public function __construct(
            private DIC\Service $logger = new DIC\Value(new NullLogger()),
        ) {}

        /**
         * @return DIC\Service<MessageBus>
         */
        public function __invoke(DIC $dic): DIC\Service
        {
            $dic->bind(LoggerInterface::class, $this->logger);

            return $dic
                ->object(MessageBus::class)
                ->args([
                    'handlers' => $dic->taggedList(AsHandler::Tag),
                ]);
        }
    }
}

namespace Authentication
{
    use MessageBus\AsHandler;
    use MessageBus\Handler;
    use Psr\Clock\ClockInterface;
    use Thesis\DIC;

    final readonly class Clock implements ClockInterface
    {
        public function now(): \DateTimeImmutable
        {
            return new \DateTimeImmutable();
        }
    }

    final readonly class ChangePasswordHandler implements Handler
    {
        public function __construct(
            public ClockInterface $clock,
        ) {}
    }

    final readonly class Module
    {
        public function __invoke(DIC $dic): void
        {
            $dic->value(new Clock())
                ->bindAs(ClockInterface::class);

            $dic->object(ChangePasswordHandler::class)
                ->tag(AsHandler::Tag);
        }
    }
}

namespace Project
{
    use Authentication;
    use MessageBus;
    use Monolog\Handler\StreamHandler;
    use Monolog\Logger;
    use Monolog\Processor\PsrLogMessageProcessor;
    use Thesis\DIC;
    use Thesis\DIC\Service;

    final readonly class App
    {
        /**
         * @return array{
         *     bus: Service<MessageBus\MessageBus>,
         * }
         */
        public function __invoke(DIC $dic): array
        {
            $streamHandler = $dic->factory(static function (): StreamHandler {
                $logHandler = new StreamHandler(STDOUT);
                $logHandler->pushProcessor(new PsrLogMessageProcessor());

                return $logHandler;
            });

            $dic->require(new Authentication\Module());

            $messageBus = $dic->require(new MessageBus\Component(
                logger: $dic
                    ->object(Logger::class)
                    ->args(['message_bus', [$streamHandler]]),
            ));

            return [
                'bus' => $messageBus,
            ];
        }
    }
}

namespace IndexPhp
{
    use Project\App;
    use Thesis\DIC;

    $exports = DIC::setup(new App());

    $exports['bus']->value->run();
}
