<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Thesis\Dic\BuildError;
use Thesis\Dic\Configuration\FunctionAutoconfig;
use Thesis\Dic\Configuration\FunctionConfig;
use Thesis\Dic\Configuration\ObjectAutoconfig;
use Thesis\Dic\Configuration\ObjectConfig;

/**
 * @internal
 */
final class Autoconfiguration
{
    public function __construct(
        private readonly Services $services,
    ) {
        $this->configs = new \SplObjectStorage();
    }

    private bool $started = false;

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
     * @var \SplObjectStorage<FunctionConfig<callable>|ObjectConfig<object>, true>
     */
    private \SplObjectStorage $configs;

    /**
     * @param FunctionConfig<callable>|ObjectConfig<object> $config
     */
    public function autoconfigure(FunctionConfig|ObjectConfig $config): void
    {
        $this->configs[$config] = true;
    }

    /**
     * @param FunctionConfig<callable>|ObjectConfig<object> $config
     */
    public function doNotAutoconfigure(FunctionConfig|ObjectConfig $config): void
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

            if ($config instanceof FunctionConfig) {
                if ($hasFunctionCallbacks) {
                    $autoconfig = new FunctionAutoconfig($config);

                    foreach ($this->functionListeners as $callback) {
                        $callback($autoconfig);
                    }
                }
            } else {
                if ($hasObjectCallbacks) {
                    $autoconfig = new ObjectAutoconfig($this->services, $config);

                    foreach ($this->objectListeners as $callback) {
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
