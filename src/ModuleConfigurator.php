<?php

declare(strict_types=1);

namespace Thesis\DI;

/**
 * @api This interface must not be implemented in userland.
 * @template TReqs of Module
 * @template TModule of Module<TReqs>
 */
interface ModuleConfigurator
{
    /**
     * @template T
     * @param ModuleId<TReqs, covariant T> $id
     * @param Id<T> $as
     */
    public function importAs(ModuleId $id, Id $as): static;

    /**
     * @template T of object
     * @param Value<T>|Constructor<T>|Factory<T> $value
     * @param list<Tag<T>> $tags
     * @param-out Id<T> $inferredId
     */
    public function define(Value|Constructor|Factory $value, array $tags = [], ?Id &$inferredId = null): static;

    /**
     * @template T of TTagValue
     * @template TTagValue
     * @param Id<covariant T>|Value<T>|Constructor<T>|Factory<T> $value
     * @param Id<T> $as
     * @param list<Tag<TTagValue>> $tags
     * @phpstan-ignore generics.notSubtype
     */
    public function defineAs(Id|Value|Constructor|Factory $value, Id $as, array $tags = []): static;

    /**
     * @template T of object
     * @param Value<T>|Constructor<T>|Factory<T> $value
     * @param list<Tag<T>> $tags
     * @param-out Id<T> $inferredId
     */
    public function export(Value|Constructor|Factory $value, array $tags = [], ?Id &$inferredId = null): static;

    /**
     * @template T of TTagValue
     * @template TTagValue
     * @param Id<covariant T>|Value<T>|Constructor<T>|Factory<T> $value
     * @param Id<T> $as
     * @param list<Tag<TTagValue>> $tags
     * @phpstan-ignore generics.notSubtype
     */
    public function exportAs(Id|Value|Constructor|Factory $value, Id $as, array $tags = []): static;
}
