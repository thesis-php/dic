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
        throw new \BadMethodCallException(static::class . ' is not cloneable');
    }

    /**
     * @internal
     */
    final public function __serialize(): never
    {
        throw new \BadMethodCallException(static::class . ' is not serializable');
    }

    /**
     * @internal
     * @param array<mixed> $data
     */
    final public function __unserialize(array $data): never
    {
        throw new \BadMethodCallException(static::class . ' is not serializable');
    }
}
