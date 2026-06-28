<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Thesis\Dic\BuildError;
use Thesis\Dic\Configuration\Config;

/**
 * @internal
 */
final class Autoconfiguration
{
    private bool $autoconfigured = false;

    /**
     * @var list<callable(Config<*>): void>
     */
    private array $autoconfigurators = [];

    /**
     * @param callable(Config<*>): void $autoconfigurator
     */
    public function addAutoconfigurator(callable $autoconfigurator): void
    {
        if ($this->autoconfigured) {
            throw BuildError::configurationFrozen();
        }

        $this->autoconfigurators[] = $autoconfigurator;
    }

    /**
     * @var list<Config<*>>
     */
    private array $queue = [];

    /**
     * @param Config<*> $config
     */
    public function schedule(Config $config): void
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
            closure: static function (Config $config) use ($autoconfigurators): void {
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
