<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Autoconfigurator\CallableAutoconfigurator;
use Thesis\Dic\Autoconfigurator\ObjectAutoconfigurator;
use Thesis\Dic\Configurator\CallableConfigurator;
use Thesis\Dic\Configurator\ObjectConfigurator;

final class Autoconfigurators implements CallableAutoconfigurator, ObjectAutoconfigurator
{
    /**
     * @var list<CallableAutoconfigurator>
     */
    private array $callable = [];

    /**
     * @var list<ObjectAutoconfigurator>
     */
    private array $object = [];

    public function add(CallableAutoconfigurator|ObjectAutoconfigurator $autoconfigurator): void
    {
        if ($autoconfigurator instanceof CallableAutoconfigurator) {
            $this->callable[] = $autoconfigurator;
        }

        if ($autoconfigurator instanceof ObjectAutoconfigurator) {
            $this->object[] = $autoconfigurator;
        }
    }

    public function supportsCallable(\ReflectionMethod|\ReflectionFunction $reflection): bool
    {
        return array_any(
            $this->callable,
            static fn(CallableAutoconfigurator $ca) => $ca->supportsCallable($reflection),
        );
    }

    public function autoconfigureCallable(CallableConfigurator $configurator): void
    {
        foreach ($this->callable as $autoconfigurator) {
            $autoconfigurator->autoconfigureCallable($configurator);
        }
    }

    public function supportsObject(\ReflectionClass $reflection): bool
    {
        return array_any(
            $this->object,
            static fn(ObjectAutoconfigurator $oa) => $oa->supportsObject($reflection),
        );
    }

    public function autoconfigureObject(ObjectConfigurator $configurator): void
    {
        foreach ($this->object as $autoconfigurator) {
            $autoconfigurator->autoconfigureObject($configurator);
        }
    }
}
