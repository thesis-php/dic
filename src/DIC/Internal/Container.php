<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\Internal\Container\Dispatcher;
use Thesis\DIC\Internal\Container\Scopeds;
use Thesis\DIC\Internal\Container\ServiceRegistrar;
use Thesis\DIC\Internal\Container\Singletons;
use Thesis\DIC\Internal\Container\Subscriber;
use Thesis\DIC\Internal\Container\Transients;
use Thesis\DIC\Ref;
use Thesis\DIC\Value;

/**
 * @internal
 */
final readonly class Container
{
    /**
     * @template T
     * @param callable(Subscriber, Tagger): Ref<T> $app
     * @return T
     */
    public static function assemble(callable $app): mixed
    {
        $dispatcher = new Dispatcher();
        $tagger = new Tagger();

        $result = $app($dispatcher, $tagger);

        $tagger->close();

        $dispatcher->resolveTags($tagger);

        $registrar = new ServiceRegistrar();

        $dispatcher->beforeAssemble($registrar);

        $container = new self(
            singletons: $registrar->singletons,
            scopeds: $registrar->scopeds,
            transients: $registrar->transients,
        );

        $dispatcher->afterAssemble();

        return $container->get($result);
    }

    private function __construct(
        private Singletons $singletons,
        private Scopeds $scopeds,
        private Transients $transients,
    ) {}

    /**
     * @template T
     * @param Ref<T> $ref
     * @return T
     */
    public function get(Ref $ref): mixed
    {
        if ($ref instanceof Value) {
            return $ref->value;
        }

        try {
            if ($this->singletons->has($ref)) {
                return $this->singletons->get($ref, $this);
            }

            if ($this->scopeds->has($ref)) {
                return $this->scopeds->get($ref, $this);
            }

            if ($this->transients->has($ref)) {
                return $this->transients->get($ref, $this);
            }
        } catch (\Throwable $exception) {
            throw new \LogicException("Invalid declaration of {$ref}", previous: $exception);
        }

        throw new \LogicException("Invalid reference for {$ref}");
    }

    public function resolve(mixed $value): mixed
    {
        if ($value instanceof Ref) {
            return $this->get($value);
        }

        if (\is_array($value)) {
            return array_map($this->resolve(...), $value);
        }

        return $value;
    }

    /**
     * @param list<Binding<*>> $bindings
     */
    public function scoped(array $bindings = []): self
    {
        $autowiring = new Autowiring();

        array_map($autowiring->addBinding(...), $bindings);

        return new self(
            singletons: $this->singletons,
            scopeds: $this->scopeds->autowire($autowiring),
            transients: $this->transients->autowire($autowiring),
        );
    }
}
