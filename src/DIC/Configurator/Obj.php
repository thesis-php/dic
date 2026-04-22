<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

use Thesis\DIC\Internal\AutowireableFactory\Arguments;
use Thesis\DIC\Internal\AutowireableFactory\Call;
use Thesis\DIC\Internal\AutowireableFactory\LazyObject;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container\ServiceRegistrar;
use Thesis\DIC\Internal\Container\Subscriber;
use Thesis\DIC\Internal\Tagger;
use Thesis\DIC\Lifetime;
use Thesis\DIC\Location;
use Thesis\DIC\Mapping\Scoped;
use Thesis\DIC\Mapping\Transient;
use Thesis\DIC\Ref;
use Thesis\DIC\Tag;

/**
 * @api
 *
 * @template-covariant T of object
 * @implements Ref<T>
 */
final class Obj implements Ref
{
    use HasArgs;
    use HasDescription;
    use HasLazy;
    use HasLifetime;

    /**
     * @use HasTags<T>
     */
    use HasTags;

    /**
     * @internal
     *
     * @param class-string<T> $class
     */
    public function __construct(
        private readonly string $class,
        Location $declaredAt,
        private readonly Subscriber $subscriber,
        Tagger $tagger,
        private readonly Autowiring $autowiring,
    ) {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new \LogicException(\sprintf('Class `%s` is not instantiable', $class));
        }

        $this->arguments = Arguments::fromParameters($reflection->getConstructor()?->getParameters() ?? []);
        $this->tagger = $tagger;
        $this->description = "[{$class} at {$declaredAt}]";

        $subscriber->onBeforeAssemble(
            function (ServiceRegistrar $registrar) use ($reflection, $autowiring): void {
                $arguments = $this->arguments->autowire($autowiring);

                if ($this->lifetime === Lifetime::Singleton) {
                    $arguments->ensureResolvable();
                }

                $class = $reflection->name;
                $factory = $arguments->isEmpty
                    ? static fn() => new $class()
                    : new Call(static fn(mixed ...$args) => new $class(...$args), $arguments);

                if ($this->lazy) {
                    $factory = $factory instanceof \Closure
                        ? static fn() => $reflection->newLazyProxy($factory)
                        : new LazyObject($reflection, $factory);
                }

                $registrar->register(
                    ref: $this,
                    factory: $factory,
                    lifetime: $this->lifetime,
                );
            },
        );

        foreach ($reflection->getAttributes(Tag::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $this->tag($attribute->newInstance());
        }

        $this->lifetime = match (true) {
            $reflection->getAttributes(Scoped::class) !== [] => Lifetime::Scoped,
            $reflection->getAttributes(Transient::class) !== [] => Lifetime::Transient,
            default => Lifetime::Singleton,
        };

        foreach ($reflection->getMethods() as $method) {
            foreach ($method->getAttributes(Tag::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $this->methodDeclaredAt($method->name, $declaredAt)->tag($attribute->newInstance());
            }
        }
    }

    /**
     * @param non-empty-string $name
     * @return Method<mixed>
     */
    public function method(string $name): Method
    {
        return $this->methodDeclaredAt($name, Location::caller());
    }

    /**
     * @var array<non-empty-string, Method<mixed>>
     */
    private array $methods = [];

    /**
     * @param non-empty-string $name
     * @return Method<mixed>
     */
    private function methodDeclaredAt(string $name, Location $declaredAt): Method
    {
        if (isset($this->methods[$name])) {
            return $this->methods[$name];
        }

        $reflection = new \ReflectionMethod($this->class, $name);

        if (!$reflection->isPublic()) {
            throw new \LogicException(\sprintf('Method `%s::%s()` is not public', $this->class, $name));
        }

        return $this->methods[$name] = new Method(
            reflection: $reflection,
            object: $this,
            declaredAt: $declaredAt,
            subscriber: $this->subscriber,
            tagger: $this->tagger,
            autowiring: $this->autowiring,
        );
    }
}
