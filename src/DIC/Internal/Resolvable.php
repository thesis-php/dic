<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\Internal\Resolvable\Data;
use Thesis\DIC\Service;

/**
 * @internal
 *
 * @template T
 * @template TData of Data
 * @implements Service<T>
 */
abstract class Resolvable implements Service
{
    /**
     * @var TData|\Closure(): T
     */
    private Data|\Closure $state;

    final public mixed $value {
        get => $this->state instanceof \Closure
            ? ($this->state)()
            : throw new \LogicException('Not resolved');
    }

    /**
     * @var TData
     */
    final protected Data $data {
        get => $this->state instanceof Data
            ? $this->state
            : throw new \LogicException('Already resolved');
    }

    /**
     * @param TData $data
     */
    protected function __construct(Data $data)
    {
        $this->state = $data;
    }

    abstract public function register(Autowiring $autowiring, Tags $tags): void;

    final public function resolve(Autowiring $autowiring, Tags $tags): void
    {
        $this->state = $this->toFactory($autowiring, $tags);
    }

    /**
     * @return \Closure(): T
     */
    abstract protected function toFactory(Autowiring $autowiring, Tags $tags): \Closure;
}
