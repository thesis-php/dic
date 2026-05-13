<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\Value;
use Thesis\Dic\Lifetime;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use function Thesis\Formatter\format;

/**
 * @api
 *
 * @template T
 * @extends Ref<T>
 */
final class ValueConfigurator extends Ref
{
    /** @use Internal\Bind<T> */
    use Internal\Bind;

    /** @use Internal\Tag<T> */
    use Internal\Tag;

    /** @use Internal\Disposer<T> */
    use Internal\Disposer;

    public readonly Lifetime $lifetime;

    protected readonly null|\ReflectionFunction|\ReflectionMethod|\ReflectionClass $reflection;

    /**
     * @internal
     */
    public function __construct(
        private readonly mixed $value,
        Location $declaredAt,
        Autowiring $autowiring,
        ContainerBuilder $containerBuilder,
    ) {
        $this->lifetime = self::hasScoped($value) ? Lifetime::Scoped : Lifetime::Singleton;
        $this->reflection = self::reflect($value);

        parent::__construct(
            label: format($value),
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            containerBuilder: $containerBuilder,
        );
    }

    protected function createFactory(): Factory
    {
        /** @var Factory<T> */
        return Value::from($this->value);
    }

    private static function hasScoped(mixed $value): bool
    {
        if ($value instanceof Ref) {
            return $value->lifetime === Lifetime::Scoped;
        }

        if (\is_array($value)) {
            return array_any($value, self::hasScoped(...));
        }

        return false;
    }

    /**
     * @return null|\ReflectionFunction|\ReflectionMethod|\ReflectionClass<*>
     */
    private static function reflect(mixed $value): null|\ReflectionFunction|\ReflectionMethod|\ReflectionClass
    {
        if ($value instanceof Ref) {
            return $value->reflection;
        }

        if ($value instanceof \Closure) {
            return new \ReflectionFunction($value);
        }

        if (\is_object($value)) {
            return new \ReflectionObject($value);
        }

        if (\is_callable($value)) {
            return new \ReflectionFunction($value(...));
        }

        if (\is_array($value)) {
            if (\count($value) === 2
                && isset($value[0]) && $value[0] instanceof Ref && ($class = $value[0]->reflection) instanceof \ReflectionClass
                && isset($value[1]) && \is_string($method = $value[1])
            ) {
                try {
                    $reflection = $class->getMethod($method);
                } catch (\ReflectionException) {
                    return null;
                }

                if ($reflection->isPublic()) {
                    return $reflection;
                }
            }
        }

        return null;
    }
}
