<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowireableFactory;

use Thesis\DIC\Internal\AutowireableFactory;
use Thesis\DIC\Internal\AutowireableFactory\Argument\Undefined;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;
use Thesis\DIC\Ref;

/**
 * @internal
 *
 * @template-covariant T
 * @implements AutowireableFactory<\Closure(): T>
 */
final readonly class Method implements AutowireableFactory
{
    /**
     * @param Ref<object> $object
     */
    public function __construct(
        private \ReflectionMethod $reflection,
        private Ref $object,
        private Arguments $arguments,
    ) {}

    public function autowire(Autowiring $autowiring): AutowireableFactory
    {
        /** @var self<T> */
        return new self(
            reflection: $this->reflection,
            object: $this->object,
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

        $__objResolver = fn() => $container->get($this->object);
        $__method = $this->reflection->name;
        $__argsResolver = static fn() => array_map(
            static fn(Argument $argument) => $argument->resolve($container),
            $resolvers,
        );

        $returnType = $this->reflection->getReturnType();
        $isVoid = $returnType instanceof \ReflectionNamedType && $returnType->getName() === 'void';

        $return = $isVoid ? '' : 'return ';
        $argumentsList = implode(', ', $arguments);

        $code = Printer::closure(
            attributes: $this->reflection->getAttributes(),
            static: $this->reflection->isStatic(),
            parameters: $parameters,
            usedVariables: [
                new UsedVariable('__objResolver', byReference: true),
                new UsedVariable('__method'),
                new UsedVariable('__argsResolver', byReference: true),
            ],
            returnType: $returnType,
            body: <<<PHP
                static \$__obj = \$__objResolver();
                \$__objResolver = null;
                
                static \$__args = \$__argsResolver();
                \$__argsResolver = null;

                {$return}\$__obj->{$__method}({$argumentsList});
                PHP,
        );

        /** @var \Closure(): T */
        return eval('return ' . $code . ';');
    }
}
