<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Config\DefineIdConfig;
use Thesis\DI\Config\DefineObjectConfig;
use Thesis\DI\Internal\Exports;
use Thesis\DI\Internal\ModuleValues;
use Thesis\DI\Internal\Tags;

/**
 * @api This interface must not be implemented in userland.
 * @template TReqs of Module
 */
interface ModuleConfig
{
    /**
     * @template TValue
     * @param ModuleId<TReqs, TValue> $moduleId
     * @param class-string<TValue&object>|Id<TValue> $as
     * @return self<TReqs>
     */
    public function import(ModuleId $moduleId, string|Id $as): self;

    /**
     * @template TValue
     * @param null|class-string<TValue&object>|Id<TValue> $id
     * @return ($id is null ? DefineObjectConfig<TReqs> : DefineIdConfig<TReqs, TValue>)
     */
    public function define(null|string|Id $id = null): DefineObjectConfig|DefineIdConfig;

    /**
     * @template TValue
     * @param null|class-string<TValue&object>|Id<TValue> $id
     * @return ($id is null ? DefineObjectConfig<TReqs> : DefineIdConfig<TReqs, TValue>)
     */
    public function export(null|string|Id $id = null): DefineObjectConfig|DefineIdConfig;

    /**
     * @internal
     * @return array{Exports, Tags, ModuleValues}
     */
    public function __invoke(): array;
}
