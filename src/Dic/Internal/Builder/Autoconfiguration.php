<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Thesis\Dic\BuildError;
use Thesis\Dic\Configuration\FunctionAutoconfig;
use Thesis\Dic\Configuration\FunctionConfig;
use Thesis\Dic\Configuration\ObjectAutoconfig;
use Thesis\Dic\Configuration\ObjectConfig;
use Thesis\Dic\Internal\Builder;

/**
 * @internal
 */
final class Autoconfiguration
{
    public function __construct(
        private readonly Builder $builder,
    ) {
        $this->configs = new \SplObjectStorage();
    }

    private bool $started = false;

    /**
     * @var list<callable(ObjectAutoconfig<object>): void>
     */
    private array $objectListeners = [];

    /**
     * @param callable(ObjectAutoconfig<object>): void $listener
     */
    public function onObject(callable $listener): void
    {
        if ($this->started) {
            throw BuildError::configurationFrozen();
        }

        $this->objectListeners[] = $listener;
    }

    /**
     * @var list<callable(FunctionAutoconfig): void>
     */
    private array $functionListeners = [];

    /**
     * @param callable(FunctionAutoconfig): void $listener
     */
    public function onFunction(callable $listener): void
    {
        if ($this->started) {
            throw BuildError::configurationFrozen();
        }

        $this->functionListeners[] = $listener;
    }

    /**
     * @var \SplObjectStorage<ObjectConfig<object>|FunctionConfig<callable>, true>
     */
    private \SplObjectStorage $configs;

    /**
     * @param ObjectConfig<object>|FunctionConfig<callable> $config
     */
    public function schedule(ObjectConfig|FunctionConfig $config): void
    {
        $this->configs[$config] = true;
    }

    /**
     * @param ObjectConfig<object>|FunctionConfig<callable> $config
     */
    public function unschedule(ObjectConfig|FunctionConfig $config): void
    {
        unset($this->configs[$config]);
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        $this->started = true;

        $hasObjectCallbacks = $this->objectListeners !== [];
        $hasFunctionCallbacks = $this->functionListeners !== [];

        if (!$hasObjectCallbacks && !$hasFunctionCallbacks) {
            $this->configs = new \SplObjectStorage();

            return;
        }

        $this->configs->rewind();

        while ($this->configs->valid()) {
            $config = $this->configs->current();

            if ($config instanceof ObjectConfig) {
                if ($hasObjectCallbacks) {
                    $autoconfig = new ObjectAutoconfig($this->builder, $config);

                    foreach ($this->objectListeners as $callback) {
                        $callback($autoconfig);
                    }
                }
            } else {
                if ($hasFunctionCallbacks) {
                    $autoconfig = new FunctionAutoconfig($config);

                    foreach ($this->functionListeners as $callback) {
                        $callback($autoconfig);
                    }
                }
            }

            unset($this->configs[$config]);
        }

        $this->objectListeners = [];
        $this->functionListeners = [];
        $this->configs = new \SplObjectStorage();
    }
}
