<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Autowire;
use Thesis\Dic\DoNotAutowire;
use Thesis\Dic\Internal\Factory\Closure;
use Thesis\Dic\Internal\Factory\Value;
use Thesis\Dic\Lifetime;
use Thesis\Dic\Ref;
use function Thesis\Formatter\formatClass;
use function Thesis\Formatter\formatReflectedFunction;
use function Thesis\Formatter\formatReflectedParameter;
use const Thesis\Dic\autowire;
use const Thesis\Dic\doNotAutowire;

/**
 * @internal
 */
final class Arguments
{
    /**
     * @param array<non-negative-int|non-empty-string, mixed> $values
     */
    public static function forFunction(\ReflectionFunctionAbstract $function, array $values = []): self
    {
        $args = new self(
            functionName: formatReflectedFunction($function),
            parameters: $function->getParameters(),
        );

        $default = self::firstAttribute($function) ?? autowire;

        foreach ($function->getParameters() as $position => $parameter) {
            $args->values[$position] = self::firstAttribute($parameter) ?? $default;
        }

        foreach ($values as $positionOrName => $value) {
            $args->set($positionOrName, $value);
        }

        return $args;
    }

    private static function firstAttribute(\ReflectionFunctionAbstract|\ReflectionParameter $reflection): null|Autowire|DoNotAutowire
    {
        $attribute = $reflection->getAttributes(Autowire::class)[0]
            ?? $reflection->getAttributes(DoNotAutowire::class)[0]
            ?? null;

        return $attribute?->newInstance();
    }

    /**
     * @param class-string $class
     */
    public static function forClassWithoutConstructor(string $class): self
    {
        return new self(
            functionName: \sprintf('%s::__construct()', formatClass($class)),
            parameters: [],
        );
    }

    /**
     * @var array<string, non-negative-int>
     */
    private readonly array $positionsByName;

    /**
     * @var array<non-negative-int, mixed>
     */
    private array $values = [];

    /**
     * @param non-empty-string $functionName
     * @param list<\ReflectionParameter> $parameters
     */
    private function __construct(
        private readonly string $functionName,
        private readonly array $parameters,
    ) {
        $this->positionsByName = array_flip(array_column($parameters, 'name'));
    }

    public function autowire(string|\Stringable|\UnitEnum $qualifier): void
    {
        $this->values = array_fill(0, \count($this->parameters), new Autowire($qualifier));
    }

    public function doNotAutowire(): void
    {
        $this->values = array_fill(0, \count($this->parameters), doNotAutowire);
    }

    public function set(int|string $positionOrName, mixed $value): void
    {
        if ($this->parameters === []) {
            throw new \LogicException("{$this->functionName} has no parameters");
        }

        if (\is_int($positionOrName)) {
            if (!isset($this->parameters[$positionOrName])) {
                throw new \LogicException("{$this->functionName} has no parameter #{$positionOrName}");
            }

            $this->values[$positionOrName] = $value;

            return;
        }

        $position = $this->positionsByName[$positionOrName]
            ?? throw new \LogicException("{$this->functionName} has no parameter \${$positionOrName}");

        $this->values[$position] = $value;
    }

    /**
     * @param Ref<*> $ref
     */
    public function resolve(Ref $ref, Autowiring $autowiring): ResolvedArguments
    {
        return new ResolvedArguments(
            list: array_map(
                fn(\ReflectionParameter $p) => $this->resolveArgument($p, $ref, $autowiring),
                $this->parameters,
            ),
        );
    }

    /**
     * @param Ref<*> $ref
     */
    private function resolveArgument(\ReflectionParameter $parameter, Ref $ref, Autowiring $autowiring): Factory|\ReflectionParameter
    {
        $value = $this->values[$parameter->getPosition()] ?? autowire;

        if ($value instanceof DoNotAutowire) {
            return $parameter;
        }

        if ($value instanceof Autowire) {
            $type = TypeReflector::parameterType($parameter);

            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    return new Closure(static fn() => $parameter->getDefaultValue());
                }

                throw new \LogicException(\sprintf(
                    'Required parameter `%s` does not have a type to be autowired',
                    formatReflectedParameter($parameter),
                ));
            }

            $value = $autowiring->autowire($type, $value->qualifier);

            if ($value === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    return new Closure(static fn() => $parameter->getDefaultValue());
                }

                throw new \LogicException(\sprintf(
                    'No bound autowiring services match required parameter `%s`',
                    formatReflectedParameter($parameter),
                ));
            }
        }

        self::validate($value, "\${$parameter->name}", $ref);

        return Value::from($value);
    }

    /**
     * @param Ref<*> $ref
     * @param non-empty-string $path
     */
    private static function validate(mixed $value, string $path, Ref $ref): void
    {
        if (\is_array($value)) {
            array_walk($value, static fn(mixed $item, int|string $key) => self::validate(
                value: $item,
                path: $path . '.' . $key,
                ref: $ref,
            ));

            return;
        }

        if (!$value instanceof Ref) {
            return;
        }

        if ($value === $ref) {
            throw new \LogicException("Cyclic dependency: {$ref} depends on itself at {$path}");
        }

        if ($value->lifetime === Lifetime::Scoped && $ref->lifetime === Lifetime::Singleton) {
            throw new \LogicException("Cannot inject scoped service {$value} into singleton {$ref} at {$path}");
        }
    }
}
