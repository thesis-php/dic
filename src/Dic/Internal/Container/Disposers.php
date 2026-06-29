<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Container;

use Thesis\Dic\Internal\NonCopyable;
use Thesis\Dic\Ref;

/**
 * @internal
 */
final readonly class Disposers
{
    use NonCopyable;

    /**
     * @var \WeakMap<Ref<mixed>, list<callable(mixed, ?\Throwable): void>>
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
     * Runs every disposer registered for $ref, never stopping on failure, and
     * returns the throwables they raised.
     *
     * @template T
     * @param Ref<T> $ref
     * @param T $value
     * @return list<\Throwable>
     */
    public function dispose(Ref $ref, mixed $value, ?\Throwable $error): array
    {
        $disposers = $this->disposers[$ref] ?? [];

        if ($disposers === []) {
            return [];
        }

        if (\is_object($value) && new \ReflectionObject($value)->isUninitializedLazyObject($value)) {
            return [];
        }

        $errors = [];

        foreach ($disposers as $disposer) {
            try {
                $disposer($value, $error);
            } catch (\Throwable $disposerError) {
                $errors[] = $disposerError;
            }
        }

        return $errors;
    }
}
