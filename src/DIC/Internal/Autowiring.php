<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

/**
 * @internal
 */
final class Autowiring
{
    /**
     * @var array<class-string, object>
     */
    private array $bindings = [];

    /**
     * @param list<class-string> $classes
     */
    public function register(object $service, array $classes): void
    {
        foreach ($classes as $class) {
            $this->bindings[$class] = $service;
        }
    }

    public function autowire(\ReflectionParameter $parameter): ?object
    {
        $type = $parameter->getType();

        if ($type instanceof \ReflectionNamedType) {
            $name = $type->getName();

            if (isset($this->bindings[$name])) {
                return $this->bindings[$name];
            }
        }

        return null;
    }
}
