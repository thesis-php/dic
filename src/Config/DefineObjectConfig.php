<?php

declare(strict_types=1);

namespace Thesis\DI\Config;

use Thesis\DI\Internal\Autowiring;
use Thesis\DI\Internal\Call;
use Thesis\DI\Internal\Construct;
use Thesis\DI\Internal\ModuleConfig;
use Thesis\DI\Internal\Value;
use Thesis\DI\Module;

/**
 * @api
 * @template TReqs of Module
 */
final readonly class DefineObjectConfig
{
    /**
     * @internal
     * @param ModuleConfig<TReqs> $moduleConfig
     */
    public function __construct(
        private ModuleConfig $moduleConfig,
        private Autowiring $autowiring,
        private bool $export = false,
    ) {}

    /**
     * @template TValue of object
     * @param TValue $object
     * @return ValueConfig<TReqs, TValue>
     */
    public function value(object $object): ValueConfig
    {
        return $this->createFactoryConfig(new Value($object));
    }

    /**
     * @template TValue of object
     * @param callable(never, never, never, never, never): TValue $function
     * @return CallAndConstructConfig<TReqs, TValue>
     */
    public function call(callable $function): CallAndConstructConfig
    {
        return $this->createFactoryConfig(new Call($function(...)));
    }

    /**
     * @template TValue of object
     * @param class-string<TValue> $class
     * @return CallAndConstructConfig<TReqs, TValue>
     */
    public function construct(string $class): CallAndConstructConfig
    {
        return $this->createFactoryConfig(new Construct($class));
    }

    /**
     * @template TValue of object
     * @param Value<TValue>|Construct<TValue>|Call<TValue> $recipe
     * @return CallAndConstructConfig<TReqs, TValue>
     */
    private function createFactoryConfig(Value|Construct|Call $recipe): CallAndConstructConfig
    {
        return new CallAndConstructConfig(
            moduleConfig: $this->moduleConfig,
            id: $this->autowiring->identify($recipe),
            export: $this->export,
            recipe: $recipe,
        );
    }
}
