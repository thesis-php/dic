<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Autowire;
use Thesis\Dic\DoNotAutowire;
use Thesis\Dic\Internal\Factory\DefaultValue;
use Thesis\Dic\Internal\Factory\ListOf;
use Thesis\Dic\Internal\Factory\Value;
use Thesis\Dic\Ref;
use function Thesis\Formatter\formatClass;
use function Thesis\Formatter\formatReflectedFunction;
use function Thesis\Formatter\formatReflectedParameter;
use const Thesis\Dic\autowire;

/**
 * @internal
 */
final class Arguments
{
    /**
     * @param array<mixed> $values
     */
    public static function forFunction(\ReflectionFunctionAbstract $function, array $values = []): self
    {
        $arguments = new self(
            functionName: formatReflectedFunction($function),
            parameters: $function->getParameters(),
        );

        $default = self::attribute($function) ?? autowire;

        foreach ($function->getParameters() as $position => $parameter) {
            $arguments->values[$position] = self::attribute($parameter) ?? $default;
        }

        foreach ($values as $positionOrName => $value) {
            $arguments->set($positionOrName, $value);
        }

        return $arguments;
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

    private static function attribute(\ReflectionFunctionAbstract|\ReflectionParameter $reflection): null|Autowire|DoNotAutowire
    {
        $attribute = $reflection->getAttributes(Autowire::class)[0]
            ?? $reflection->getAttributes(DoNotAutowire::class)[0]
            ?? null;

        return $attribute?->newInstance();
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
     * @param list<\ReflectionParameter> $parameters
     */
    private function __construct(
        private readonly string $functionName,
        private readonly array $parameters,
    ) {
        $this->positionsByName = array_flip(array_column($parameters, 'name'));
    }

    public function set(int|string $parameter, mixed $value): void
    {
        if ($this->parameters === []) {
            throw new \LogicException("{$this->functionName} has no parameters");
        }

        if (\is_int($parameter)) {
            if (!isset($this->parameters[$parameter])) {
                throw new \LogicException("{$this->functionName} has no parameter #{$parameter}");
            }

            $this->values[$parameter] = $value;

            return;
        }

        $position = $this->positionsByName[$parameter]
            ?? throw new \LogicException("{$this->functionName} has no parameter \${$parameter}");

        $this->values[$position] = $value;
    }

    public function fill(mixed $value): void
    {
        $this->values = array_fill(0, \count($this->parameters), $value);
    }

    /**
     * @param Ref<*> $ref
     * @return Factory<list<mixed>>
     */
    public function resolve(Ref $ref, Autowiring $autowiring): Factory
    {
        return new ListOf(array_map(
            fn(\ReflectionParameter $p) => $this->resolveArgument($p, $ref, $autowiring),
            $this->parameters,
        ));
    }

    /**
     * @param Ref<*> $ref
     */
    private function resolveArgument(\ReflectionParameter $parameter, Ref $ref, Autowiring $autowiring): Factory
    {
        $value = $this->values[$parameter->getPosition()] ?? autowire;

        if ($value instanceof DoNotAutowire) {
            if ($parameter->isDefaultValueAvailable()) {
                return new DefaultValue($parameter);
            }

            throw new \LogicException(\sprintf(
                'Parameter %s is not autowired and has no default value',
                formatReflectedParameter($parameter),
            ));
        }

        if ($value instanceof Autowire) {
            $type = TypeReflector::parameterType($parameter);

            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    return new DefaultValue($parameter);
                }

                throw new \LogicException(\sprintf(
                    'Required parameter `%s` does not have a type to be autowired',
                    formatReflectedParameter($parameter),
                ));
            }

            $value = $autowiring->autowire($type, $value->qualifier);

            if ($value === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    return new DefaultValue($parameter);
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
    }
}
