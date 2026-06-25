<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\Arguments;
use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Dependency;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\ShouldNotHappen;
use Thesis\Dic\Internal\Signature\ClosureDefaultValue;
use Thesis\Dic\Internal\Signature\ClosureVariables;
use Thesis\Dic\Internal\Signature\DefaultValue;
use Thesis\Dic\Internal\Signature\Parameter;
use Thesis\Dic\Ref;
use Typhoon\Type\ClosureT;
use Typhoon\Type\Parameter as ClosureParameter;

/**
 * @internal
 *
 * @template T of \Closure
 * @implements Factory<T>
 */
final readonly class ClosureFactory implements Factory
{
    /**
     * @param Ref<callable> $function
     * @param Arguments<ClosureParameter> $arguments
     * @return self<\Closure>
     */
    public static function from(
        ClosureT $type,
        Ref $function,
        Arguments $arguments,
    ): self {
        $variables = new ClosureVariables($type);

        $code = <<<PHP
            \${$variables->function} = \$function;
            \${$variables->appliedArguments} = \$appliedArguments;
            
            return static fn(
            PHP;

        foreach ($type->parameters as $position => $parameter) {
            if ($position > 0) {
                $code .= ', ';
            }

            $code .= \sprintf(
                'mixed %s%s$%s%s',
                $parameter->isPassedByReference ? '&' : '',
                $parameter->isVariadic ? '...' : '',
                $variables->parameter($parameter),
                $parameter->hasDefault ? ' = ' . ClosureDefaultValue::Value->print() : '',
            );
        }

        $code .= ") => \${$variables->function}(";

        $factories = [];

        foreach ($arguments->resolve() as [$parameter, $argument]) {
            $code .= self::printArgument(
                parameter: $parameter,
                argument: $argument,
                variables: $variables,
                factories: $factories,
            );
        }

        $code .= ');';

        return new self(
            function: $function,
            code: $code,
            factories: $factories,
        );
    }

    /**
     * @param array<non-empty-string, ValueFactory|DefaultValue> $factories
     */
    private static function printArgument(
        Parameter $parameter,
        ValueFactory|ClosureParameter|DefaultValue $argument,
        ClosureVariables $variables,
        array &$factories,
    ): string {
        $code = '';

        if ($parameter->position > 0) {
            $code .= ', ';
        }

        if ($parameter->isVariadic) {
            $code .= '...';
        }

        if (!$argument instanceof ClosureParameter) {
            $factories[$parameter->name] = $argument;
            $key = var_export($parameter->name, return: true);

            return "{$code}\${$variables->appliedArguments}[{$key}]";
        }

        $variable = $variables->parameter($argument);

        if ($argument->hasDefault && $parameter->defaultValue !== ClosureDefaultValue::Value) {
            return \sprintf(
                '%s$%s === %s ? %s : $%s',
                $code,
                $variable,
                ClosureDefaultValue::Value->print(),
                $parameter->defaultValue?->print() ?? throw new ShouldNotHappen('Optional parameter has no printable default value'),
                $variable,
            );
        }

        return "{$code}\${$variable}";
    }

    /**
     * @param Ref<callable> $function
     * @param non-empty-string $code
     * @param array<non-empty-string, ValueFactory|DefaultValue> $factories
     */
    private function __construct(
        private Ref $function,
        private string $code,
        private array $factories,
    ) {}

    public function dependencies(): iterable
    {
        yield Dependency::factory($this->function);

        foreach ($this->factories as $name => $factory) {
            if ($factory instanceof Factory) {
                foreach ($factory->dependencies() as $dependency) {
                    yield $dependency->arg($name);
                }
            }
        }
    }

    public function create(Container $container): mixed
    {
        $function = $container->get($this->function);
        $appliedArguments = array_map(
            static fn(DefaultValue|Factory $factory) => $factory->create($container),
            $this->factories,
        );

        /** @var T */
        return eval($this->code);
    }
}
