<?php

declare(strict_types=1);

namespace Thesis\DI\Config;

use Thesis\DI\Id;
use Thesis\DI\Internal\Call;
use Thesis\DI\Internal\Construct;
use Thesis\DI\Internal\ModuleConfig;
use Thesis\DI\Internal\Value;
use Thesis\DI\Module;
use Thesis\DI\Recipe;

/**
 * @api
 * @template TReqs of Module
 * @template TValue
 */
final readonly class DefineIdConfig
{
    /**
     * @internal
     * @param ModuleConfig<TReqs> $moduleConfig
     * @param Id<TValue> $id
     */
    public function __construct(
        private ModuleConfig $moduleConfig,
        private Id $id,
        private bool $export = false,
    ) {}

    /**
     * @param TValue $value
     * @return ValueConfig<TReqs, TValue>
     */
    public function value(mixed $value): ValueConfig
    {
        return $this->createFactoryConfig(new Value($value));
    }

    /**
     * @param callable(never, never, never, never, never): TValue $function
     * @return CallAndConstructConfig<TReqs, TValue>
     */
    public function call(callable $function): CallAndConstructConfig
    {
        return $this->createFactoryConfig(new Call($function(...)));
    }

    /**
     * @param class-string<TValue&object> $class
     * @return CallAndConstructConfig<TReqs, TValue>
     */
    public function construct(string $class): CallAndConstructConfig
    {
        return $this->createFactoryConfig(new Construct($class));
    }

    /**
     * @param Recipe<TValue> $recipe
     * @return CallAndConstructConfig<TReqs, TValue>
     */
    private function createFactoryConfig(Recipe $recipe): CallAndConstructConfig
    {
        return new CallAndConstructConfig(
            moduleConfig: $this->moduleConfig,
            id: $this->id,
            export: $this->export,
            recipe: $recipe,
        );
    }
}
