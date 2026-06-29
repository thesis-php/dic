<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Thesis\Dic\BuildError;
use Thesis\Dic\Configuration\Config;
use Thesis\Dic\Configuration\FunctionConfig;
use Thesis\Dic\Configuration\ObjectConfig;

/**
 * @internal
 */
final class Autoconfiguration
{
    private bool $autoconfigured = false;

    /**
     * @var list<callable(FunctionConfig<*>|ObjectConfig<*>): void>
     */
    private array $autoconfigurators = [];

    /**
     * @param callable(FunctionConfig<*>|ObjectConfig<*>): void $autoconfigurator
     */
    public function addAutoconfigurator(callable $autoconfigurator): void
    {
        if ($this->autoconfigured) {
            throw BuildError::configurationFrozen();
        }

        $this->autoconfigurators[] = $autoconfigurator;
    }

    /**
     * @var list<FunctionConfig<*>|ObjectConfig<*>>
     */
    private array $queue = [];

    /**
     * @param FunctionConfig<*>|ObjectConfig<*> $config
     */
    public function schedule(FunctionConfig|ObjectConfig $config): void
    {
        if (!$this->autoconfigured) {
            $this->queue[] = $config;
        }
    }

    public function autoconfigure(): void
    {
        if ($this->autoconfigured) {
            return;
        }

        $this->autoconfigured = true;

        if ($this->autoconfigurators === []) {
            $this->queue = [];

            return;
        }

        $autoconfigurators = $this->autoconfigurators;
        $autoconfigurator = \Closure::bind(
            closure: static function (FunctionConfig|ObjectConfig $config) use ($autoconfigurators): void {
                if (!$config->isAutoconfigurable) {
                    return;
                }

                $config->isAutoconfiguring = true;

                try {
                    foreach ($autoconfigurators as $autoconfigurator) {
                        $autoconfigurator($config);
                    }
                } finally {
                    $config->isAutoconfiguring = false;
                }
            },
            newThis: null,
            newScope: Config::class,
        );

        foreach ($this->queue as $config) {
            $autoconfigurator($config);
        }

        $this->autoconfigurators = [];
        $this->queue = [];
    }
}
