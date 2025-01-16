<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\Autowiring;
use Thesis\DI\Internal\Exports;
use Thesis\DI\Internal\InvalidConfig;
use Thesis\DI\Internal\Location;
use Thesis\DI\Internal\ModuleConfig;
use Thesis\DI\Internal\ModuleValues;
use Thesis\DI\Internal\Tagged;
use Thesis\DI\Internal\Values;

/**
 * @api
 * @template TReqs of Module
 */
final readonly class ContainerConfig
{
    /**
     * @return self<never>
     */
    public static function create(): self
    {
        /** @var self<never> */
        return new self(
            autowiring: new Autowiring(),
            values: Values::create(),
            exports: Exports::create(),
            tagged: Tagged::create(),
        );
    }

    private function __construct(
        private Autowiring $autowiring,
        private Values $values,
        private Exports $exports,
        private Tagged $tagged,
    ) {}

    /**
     * @template TModule of Module<covariant TReqs>
     * @param TModule $module
     * @return self<TReqs|TModule>
     */
    public function require(Module $module): self
    {
        $moduleClass = $module::class;

        if ($this->values->hasModule($moduleClass)) {
            throw InvalidConfig::moduleIsAlreadyRequired($moduleClass, Location::caller());
        }

        $reflection = new \ReflectionClass($module);

        if (!$reflection->isFinal() && !$reflection->isAnonymous()) {
            throw InvalidConfig::moduleClassMustBeFinal($moduleClass, Location::caller());
        }

        /** @var ModuleConfig<TReqs> */
        $moduleConfig = new ModuleConfig(
            autowiring: $this->autowiring,
            exports: $this->exports,
            tagged: $this->tagged,
            values: ModuleValues::create(),
            module: $moduleClass,
        );
        [$exports, $tagged, $moduleValues] = $module->configureModule($moduleConfig)();

        /** @var self<TReqs|TModule> */
        return new self(
            autowiring: $this->autowiring,
            values: $this->values->with($moduleClass, $moduleValues),
            exports: $exports,
            tagged: $tagged,
        );
    }

    /**
     * @return Container<TReqs>
     */
    public function build(): Container
    {
        /** @var Container<TReqs> */
        return new Container($this->exports, $this->tagged, $this->values);
    }
}
