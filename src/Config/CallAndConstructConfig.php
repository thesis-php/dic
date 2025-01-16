<?php

declare(strict_types=1);

namespace Thesis\DI\Config;

use Thesis\DI\FunctionRecipe;
use Thesis\DI\Id;
use Thesis\DI\Internal\ModuleConfig;
use Thesis\DI\Module;
use Thesis\DI\ModuleId;
use Thesis\DI\Recipe;
use Thesis\DI\Tag;

/**
 * @api
 * @template TReqs of Module
 * @template TValue
 * @implements ValueConfig<TReqs, TValue>
 */
final class CallAndConstructConfig implements ValueConfig
{
    /**
     * @internal
     * @param ModuleConfig<TReqs> $moduleConfig
     * @param Id<TValue> $id
     * @param Recipe<TValue> $recipe
     * @param list<Tag<TValue>> $tags
     */
    public function __construct(
        private readonly ModuleConfig $moduleConfig,
        private readonly Id $id,
        private readonly bool $export,
        private Recipe $recipe,
        private array $tags = [],
    ) {}

    public function args(mixed ...$args): static
    {
        \assert($this->recipe instanceof FunctionRecipe);

        $config = clone $this;
        $config->recipe = $this->recipe->args(...$args);

        return $config;
    }

    public function doNotAutowire(): static
    {
        \assert($this->recipe instanceof FunctionRecipe);

        $config = clone $this;
        $config->recipe = $this->recipe->doNotAutowire();

        return $config;
    }

    public function tags(Tag ...$tags): static
    {
        \assert($this->recipe instanceof FunctionRecipe);

        $config = clone $this;
        $config->tags = $tags;

        return $config;
    }

    public function import(ModuleId $moduleId, string|Id $as): ModuleConfig
    {
        return $this->moduleConfig()->import($moduleId, $as);
    }

    public function define(null|string|Id $id = null): DefineObjectConfig|DefineIdConfig
    {
        return $this->moduleConfig()->define($id);
    }

    public function export(null|string|Id $id = null): DefineObjectConfig|DefineIdConfig
    {
        return $this->moduleConfig()->export($id);
    }

    /**
     * @internal
     */
    public function __invoke(): array
    {
        return $this->moduleConfig()();
    }

    /**
     * @return ModuleConfig<TReqs>
     */
    private function moduleConfig(): ModuleConfig
    {
        return $this->moduleConfig->with(
            id: $this->id,
            export: $this->export,
            recipe: $this->recipe,
            tags: $this->tags,
        );
    }
}
