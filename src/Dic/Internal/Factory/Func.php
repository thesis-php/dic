<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\CodeGeneration\Printer;
use Thesis\Dic\Internal\CodeGeneration\UsedVariable;
use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\ResolvedArguments;

/**
 * @internal
 *
 * @template-covariant T
 * @implements Factory<T>
 */
final readonly class Func implements Factory
{
    /**
     * @phpstan-ignore missingType.callable
     */
    public static function from(\Closure $function, ResolvedArguments $arguments): Factory
    {
        $parameters = [];
        $factories = [];
        $argumentVars = [];

        foreach ($arguments->list as $argument) {
            if ($argument instanceof \ReflectionParameter) {
                $parameters[] = $argument;
                $argumentVars[] = '$' . $argument->name;
            } else {
                $factories[] = $argument;
                $argumentVars[] = \sprintf('$__args[%s]', array_key_last($factories));
            }
        }

        if ($factories === []) {
            return Value::from($function);
        }

        $reflection = new \ReflectionFunction($function);

        $returnType = $reflection->getReturnType();
        $isVoid = $returnType instanceof \ReflectionNamedType && $returnType->getName() === 'void';
        $return = $isVoid ? '' : 'return ';

        $argumentVars = implode(', ', $argumentVars);

        return new self(
            function: $function,
            factory: new ListOf($factories),
            code: Printer::closure(
                attributes: $reflection->getAttributes(),
                parameters: $parameters,
                usedVariables: [
                    new UsedVariable('__func'),
                    new UsedVariable('__argsFactory', byReference: true),
                ],
                returnType: $returnType,
                body: <<<PHP
                    static \$__args = \$__argsFactory();
                    \$__argsFactory = null;

                    {$return}\$__func({$argumentVars});
                    PHP,
            ),
        );
    }

    /**
     * @param non-empty-string $code
     *
     * @phpstan-ignore missingType.callable
     */
    private function __construct(
        private \Closure $function,
        private ListOf $factory,
        private string $code,
    ) {}

    public function create(Container $container): mixed
    {
        $factory = $this->factory;

        $__func = $this->function;
        $__argsFactory = static fn() => $factory->create($container);

        /** @var T */
        return eval("return {$this->code};");
    }
}
