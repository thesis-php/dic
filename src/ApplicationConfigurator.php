<?php

declare(strict_types=1);

namespace Thesis\DI;

use Thesis\DI\Internal\ApplicationExports;
use Thesis\DI\Internal\ApplicationValues;
use Thesis\DI\Internal\Autowiring;
use Thesis\DI\Internal\InvalidConfig;
use Thesis\DI\Internal\Location;
use Thesis\DI\Internal\ModuleBuilder;

/**
 * @api
 * @template TReqs of Module
 */
final readonly class ApplicationConfigurator
{
    /**
     * @return self<never>
     */
    public static function create(): self
    {
        /** @var self<never> */
        return new self(
            autowiring: new Autowiring(),
            exports: ApplicationExports::create(),
            values: ApplicationValues::create(),
        );
    }

    private function __construct(
        private Autowiring $autowiring,
        private ApplicationExports $exports,
        private ApplicationValues $values,
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
            ModuleBuilder::create(
                module: $moduleClass,
                exports: $this->exports,
                autowiring: $this->autowiring,
            ),
        );

        /** @var self<TReqs|TModule> */
        return new self(
            autowiring: $this->autowiring,
            exports: $moduleBuilder->exports,
            values: $this->values->with($moduleClass, $moduleBuilder->values),
        );
    }

    /**
     * @return Application<TReqs>
     */
    public function build(): Application
    {
        /** @var Application<TReqs> */
        return new Application($this->exports, $this->values);
    }
}
