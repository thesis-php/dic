<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ClassReflection;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\Value;
use Thesis\Dic\Internal\FunctionReflection;
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

    protected null|ClassReflection|FunctionReflection $internalReflection;

    /**
     * @internal
     */
    public function __construct(
        private readonly mixed $value,
        Location $declaredAt,
        Autowiring $autowiring,
        ContainerBuilder $containerBuilder,
    ) {
        $this->internalReflection = self::reflect($value);

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

    private static function reflect(mixed $value): null|ClassReflection|FunctionReflection
    {
        if ($value instanceof Ref) {
            return $value->internalReflection;
        }

        if ($value instanceof \Closure) {
            return FunctionReflection::fromCallable($value);
        }

        if (\is_object($value)) {
            return ClassReflection::fromClass($value::class);
        }

        if (\is_callable($value)) {
            return FunctionReflection::fromCallable($value);
        }

        if (\is_array($value)) {
            if (\count($value) === 2
                && isset($value[0]) && $value[0] instanceof Ref && ($class = $value[0]->internalReflection) instanceof ClassReflection
                && isset($value[1]) && \is_string($method = $value[1])
            ) {
                return $class->findPublicMethod($method);
            }
        }

        return null;
    }
}
