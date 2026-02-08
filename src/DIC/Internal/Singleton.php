<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

/**
 * @internal
 *
 * @template-covariant T
 */
final class Singleton
{
    /**
     * @var ?\Closure(): T
     */
    private ?\Closure $factory;

    /**
     * @var T
     * @phpstan-ignore property.uninitialized
     */
    private mixed $value;

    /**
     * @param \Closure(): T $factory
     */
    public function __construct(\Closure $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return T
     */
    public function __invoke(): mixed
    {
        if ($this->factory === null) {
            return $this->value;
        }

        $this->value = ($this->factory)();
        $this->factory = null;

        return $this->value;
    }
}
