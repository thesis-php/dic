<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

/**
 * @internal
 */
trait NonCopyable
{
    /**
     * @internal
     */
    final public function __clone(): never
    {
        throw new \BadMethodCallException(\sprintf('"%s" is not cloneable', static::class));
    }

    /**
     * @internal
     */
    final public function __serialize(): never
    {
        throw new \BadMethodCallException(\sprintf('"%s" is not serializable', static::class));
    }

    /**
     * @internal
     * @param array<mixed> $data
     */
    final public function __unserialize(array $data): never
    {
        throw new \BadMethodCallException(\sprintf('"%s" is not serializable', static::class));
    }
}
