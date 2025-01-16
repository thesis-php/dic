<?php

declare(strict_types=1);

use Thesis\DI\ContainerConfig;
use Thesis\DI\Id;
use Thesis\DI\Module;
use Thesis\DI\ModuleConfig;
use Thesis\DI\ModuleId;
use Thesis\DI\Tag;
use function Thesis\DI\moduleId;
use function Thesis\DI\objectId;
use function Thesis\DI\taggedList;

require_once __DIR__ . '/vendor/autoload.php';

interface Logger {}

/**
 * @implements Tag<Logger>
 */
enum ChannelTag implements Tag
{
    case Tag;
}

final readonly class NullLogger implements Logger {}

final readonly class Channels
{
    /**
     * @param list<Logger> $loggers
     */
    public function __construct(
        public array $loggers,
    ) {}
}

/**
 * @api
 */
final readonly class LoggingModule implements Module
{
    /**
     * @param non-empty-string $channel
     * @return ModuleId<self, Logger>
     */
    public static function channelId(string $channel): ModuleId
    {
        /** @var ModuleId<self, Logger> */
        return new ModuleId(LoggingModule::class, new Id('logger.' . $channel));
    }

    /**
     * @param list<non-empty-string> $channels
     */
    public function __construct(
        private array $channels = [],
    ) {}

    public function configureModule(ModuleConfig $config): ModuleConfig
    {
        foreach ($this->channels as $channel) {
            $config = $config
                ->export(self::channelId($channel)->id)
                    ->construct(NullLogger::class)
                    ->tags(ChannelTag::Tag);
        }

        return $config
            ->export()
                ->construct(Channels::class)
                ->args(taggedList(ChannelTag::class));
    }
}

/**
 * @api
 */
final readonly class MyService
{
    public function __construct(public Logger $logger) {}
}

/**
 * @api
 */
final readonly class AnotherService
{
    public static function create(Logger $logger): self
    {
        return new self($logger);
    }

    private function __construct(public Logger $logger) {}
}

/**
 * @api
 * @implements Module<LoggingModule>
 */
final readonly class MyModule implements Module
{
    public function configureModule(ModuleConfig $config): ModuleConfig
    {
        return $config
            ->import(LoggingModule::channelId('app'), objectId(Logger::class))
            ->export()
                ->construct(MyService::class)
            ->export()
                ->call(AnotherService::create(...))
        ;
    }
}

$container = ContainerConfig::create()
    ->require(new LoggingModule(['app', 'messaging']))
    ->require(new MyModule())
    ->build();

dump($container->get(moduleId(LoggingModule::class, objectId(Channels::class))));
dump($container->get(moduleId(MyModule::class, objectId(MyService::class))));
dump($container->get(moduleId(MyModule::class, objectId(AnotherService::class))));
