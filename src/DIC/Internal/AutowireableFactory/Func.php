<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowireableFactory;

use Thesis\DIC\Internal\AutowireableFactory;
use Thesis\DIC\Internal\AutowireableFactory\Argument\Undefined;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;

/**
 * @internal
 *
 * @template-covariant T
 * @implements AutowireableFactory<\Closure(): T>
 */
final readonly class Func implements AutowireableFactory
{
    /**
     * @param \Closure(): T $function
     */
    public function __construct(
        private \Closure $function,
        private Arguments $arguments,
    ) {}

    public function autowire(Autowiring $autowiring): AutowireableFactory
    {
        return new self(
            function: $this->function,
            arguments: $this->arguments->autowire($autowiring),
        );
    }

    public function __invoke(Container $container): mixed
    {
        $parameters = [];
        $resolvers = [];
        $arguments = [];

        foreach ($this->arguments->arguments as $argument) {
            if ($argument instanceof Undefined) {
                $parameters[] = $argument->parameter->reflection;
                $arguments[] = '$' . $argument->parameter->name;
            } else {
                $index = \count($resolvers);
                $resolvers[] = $argument;
                $arguments[] = "\$__args[{$index}]";
            }
        }

        if ($resolvers === []) {
            return $this->function;
        }

        $__func = $this->function;
        $__argsResolver = static fn() => array_map(
            static fn(Argument $argument) => $argument->resolve($container),
            $resolvers,
        );

        $reflection = new \ReflectionFunction($this->function);
        $returnType = $reflection->getReturnType();
        $isVoid = $returnType instanceof \ReflectionNamedType && $returnType->getName() === 'void';

        $return = $isVoid ? '' : 'return ';
        $argumentsList = implode(', ', $arguments);

        $code = Printer::closure(
            attributes: $reflection->getAttributes(),
            static: $reflection->isStatic(),
            parameters: $parameters,
            usedVariables: [
                new UsedVariable('__func'),
                new UsedVariable('__argsResolver', byReference: true),
            ],
            returnType: $returnType,
            body: <<<PHP
                static \$__args = \$__argsResolver();
                \$__argsResolver = null;

                {$return}\$__func({$argumentsList});
                PHP,
        );

        /** @var \Closure(): T */
        return eval('return ' . $code . ';');
    }
}
