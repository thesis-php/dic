<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Ref;

/**
 * @internal
 */
final readonly class Disposers
{
    /**
     * @var \WeakMap<Ref<*>, list<callable(mixed, ?\Throwable): void>>
     */
    private \WeakMap $disposers;

    public function __construct()
    {
        $this->disposers = new \WeakMap();
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @param callable(T, ?\Throwable): void $disposer
     */
    public function add(Ref $ref, callable $disposer): void
    {
        $this->disposers->offsetSet($ref, [
            ...($this->disposers[$ref] ?? []),
            $disposer,
        ]);
    }

    /**
     * @template T
     * @param Ref<T> $ref
     * @param T $value
     */
    public function dispose(Ref $ref, mixed $value, ?\Throwable $error): void
    {
        foreach ($this->disposers[$ref] ?? [] as $disposer) {
            $disposer($value, $error);
        }
    }
}
