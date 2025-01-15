<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\Autowiring;
use Thesis\DI\Internal\Exports;
use Thesis\DI\Internal\InvalidConfig;
use Thesis\DI\Internal\Location;
use Thesis\DI\Internal\ModuleConfigurator;
use Thesis\DI\Internal\Tagged;
use Thesis\DI\Internal\Values;

/**
 * @api
 * @template TReqs of Module
 */
final readonly class ContainerConfigurator
{
    /**
     * @return self<never>
     */
    public static function create(): self
    {
        /** @var self<never> */
        return new self(
            autowiring: new Autowiring(),
            exports: Exports::create(),
            values: Values::create(),
            tagged: Tagged::create(),
        );
    }

    private function __construct(
        private Autowiring $autowiring,
        private Exports $exports,
        private Values $values,
        private Tagged $tagged,
    ) {}

    /**
     * @template TModule of Module<TReqs>
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

        $moduleBuilder = $module->configureModule(
            ModuleConfigurator::create(
                autowiring: $this->autowiring,
                exports: $this->exports,
                tags: $this->tagged,
                module: $moduleClass,
            ),
        );

        /** @var self<TReqs|TModule> */
        return new self(
            autowiring: $this->autowiring,
            exports: $moduleBuilder->exports,
            values: $this->values->with($moduleClass, $moduleBuilder->values),
            tagged: $moduleBuilder->tagged,
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
