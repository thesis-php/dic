<?php

declare(strict_types=1);

namespace Thesis\DI;

/**
 * @api
 * @template TReqs of Module
 * @template TModule of Module<TReqs>
 */
interface ModuleConfigurator
{
    /**
     * @template T
     * @param Id<covariant T>|ModuleId<covariant TReqs, covariant T>|Value<T>|Constructor<T&object>|Factory<T> $value
     * @param null|Id<T>|ModuleId<TModule, T> $as
     * @param-out ModuleId<TModule, T> $ref
     */
    public function define(
        Id|ModuleId|Value|Constructor|Factory $value,
        null|Id|ModuleId $as = null,
        ?ModuleId &$ref = null,
    ): static;

    /**
     * @template T
     * @param Id<covariant T>|ModuleId<covariant TReqs, covariant T>|Value<T>|Constructor<T&object>|Factory<T> $value
     * @param null|Id<T>|ModuleId<TModule, T> $as
     * @param-out ModuleId<TModule, T> $ref
     */
    public function export(
        Id|ModuleId|Value|Constructor|Factory $value,
        null|Id|ModuleId $as = null,
        ?ModuleId &$ref = null,
    ): static;
}
