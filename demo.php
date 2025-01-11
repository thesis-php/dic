<?php

declare(strict_types=1);

use Thesis\DI\ApplicationConfigurator;
use Thesis\DI\Id;
use Thesis\DI\Module;
use Thesis\DI\ModuleId;
use Thesis\DI\ModuleConfigurator;
use function Thesis\DI\moduleId;
use function Thesis\DI\objectId;
use function Thesis\DI\constructor;
use function Thesis\DI\factory;

require_once __DIR__ . '/vendor/autoload.php';

interface Logger {}

final readonly class NullLogger implements Logger {}

/**
 * @api
 * @implements Module<never>
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

    public function configureModule(ModuleConfigurator $module): ModuleConfigurator
    {
        foreach ($this->channels as $channel) {
            $module = $module->export(constructor(NullLogger::class), as: self::channelId($channel));
        }

        return $module;
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
    public function configureModule(ModuleConfigurator $module): ModuleConfigurator
    {
        return $module
            ->define(LoggingModule::channelId('app'), as: objectId(Logger::class))
            ->export(constructor(MyService::class))
            ->export(factory(AnotherService::create(...)))
        ;
    }
}

$app = ApplicationConfigurator::create()
    ->require(new LoggingModule(['app']))
    ->require(new MyModule())
    ->build()
;

$my = $app->get(moduleId(MyModule::class, objectId(MyService::class)));
$another = $app->get(moduleId(MyModule::class, objectId(AnotherService::class)));

dd($my, $another);
