<?php

declare(strict_types=1);

namespace Thesis\DI\Internal;

use Thesis\DI\Config\DefineIdConfig;
use Thesis\DI\Config\DefineObjectConfig;
use Thesis\DI\Id;
use Thesis\DI\Module;
use Thesis\DI\ModuleConfig as ModuleConfigI;
use Thesis\DI\ModuleId;
use Thesis\DI\Recipe;
use Thesis\DI\Tag;
use function Thesis\DI\moduleId;
use function Thesis\DI\objectId;

/**
 * @internal
 * @template TReqs of Module
 * @implements ModuleConfigI<TReqs>
 */
final readonly class ModuleConfig implements ModuleConfigI
{
    /**
     * @param class-string<Module> $module
     */
    public function __construct(
        private Autowiring $autowiring,
        private Exports $exports,
        private Tags $tags,
        private string $module,
        private ModuleValues $values = new ModuleValues(),
    ) {}

    /**
     * @template TValue
     * @param ModuleId<TReqs, TValue> $moduleId
     * @param class-string<TValue&object>|Id<TValue> $as
     * @return self<TReqs>
     */
    public function import(ModuleId $moduleId, string|Id $as): ModuleConfigI
    {
        if (\is_string($as)) {
            $as = objectId($as);
        }

        /** @var self<TReqs> */
        return new self(
            autowiring: $this->autowiring,
            exports: $this->exports,
            tags: $this->tags,
            module: $this->module,
            values: $this->values->with($as, $moduleId),
        );
    }

    /**
     * @template TValue
     * @param null|class-string<TValue&object>|Id<TValue> $id
     * @return ($id is null ? DefineObjectConfig<TReqs> : DefineIdConfig<TReqs, TValue>)
     */
    public function define(null|string|Id $id = null, bool $export = false): DefineObjectConfig|DefineIdConfig
    {
        if ($id === null) {
            return new DefineObjectConfig(
                moduleConfig: $this,
                autowiring: $this->autowiring,
                export: $export,
            );
        }

        if (\is_string($id)) {
            /** @var Id<TValue> */
            $id = objectId($id);
        }

        return new DefineIdConfig(
            moduleConfig: $this,
            id: $id,
            export: $export,
        );
    }

    public function export(null|string|Id $id = null): DefineObjectConfig|DefineIdConfig
    {
        return $this->define($id, export: true);
    }

    public function __invoke(): array
    {
        return [
            $this->exports,
            $this->tags,
            $this->values,
        ];
    }

    /**
     * @template TValue
     * @param Id<TValue> $id
     * @param Recipe<TValue> $recipe
     * @param list<Tag<TValue>> $tags
     * @return self<TReqs>
     */
    public function with(Id $id, bool $export, Recipe $recipe, array $tags): self
    {
        $moduleId = moduleId($this->module, $id);

        /** @var self<TReqs> */
        return new self(
            autowiring: $this->autowiring,
            exports: $export ? $this->exports->with($moduleId) : $this->exports,
            tags: $this->tags->with($moduleId, $tags),
            module: $this->module,
            values: $this->values->with($id, RecipeResolver::resolve(
                autowiring: $this->autowiring,
                exports: $this->exports,
                values: $this->values,
                module: $this->module,
                recipe: $recipe,
            )),
        );
    }
}
