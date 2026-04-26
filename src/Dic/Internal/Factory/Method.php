<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\CodeGeneration\Printer;
use Thesis\Dic\Internal\CodeGeneration\UsedVariable;
use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\ResolvedArguments;
use Thesis\Dic\Ref;

/**
 * @internal
 *
 * @template-covariant T
 * @implements Factory<\Closure(mixed...): T>
 */
final readonly class Method implements Factory
{
    /**
     * @param Ref<object> $object
     * @return self<mixed>
     */
    public static function from(
        Ref $object,
        \ReflectionMethod $reflection,
        ResolvedArguments $arguments,
    ): self {
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

        $returnType = $reflection->getReturnType();
        $isVoid = $returnType instanceof \ReflectionNamedType && $returnType->getName() === 'void';
        $return = $isVoid ? '' : 'return ';

        $argumentVars = implode(', ', $argumentVars);

        return new self(
            object: $object,
            factory: new ListOf($factories),
            code: Printer::closure(
                attributes: $reflection->getAttributes(),
                parameters: $parameters,
                usedVariables: [
                    new UsedVariable('__objFactory', byReference: true),
                    new UsedVariable('__argsFactory', byReference: true),
                ],
                returnType: $returnType,
                body: <<<PHP
                    static \$__obj = \$__objFactory();
                    \$__objFactory = null;

                    static \$__args = \$__argsFactory();
                    \$__argsFactory = null;

                    {$return}\$__obj->{$reflection->name}({$argumentVars});
                    PHP,
            ),
        );
    }

    /**
     * @param Ref<object> $object
     * @param non-empty-string $code
     */
    private function __construct(
        private Ref $object,
        private ListOf $factory,
        private string $code,
    ) {}

    public function create(Container $container): mixed
    {
        $object = $this->object;
        $factory = $this->factory;

        $__objFactory = static fn() => $container->get($object);
        $__argsFactory = static fn() => $factory->create($container);

        /** @var \Closure(mixed...): T */
        return eval("return {$this->code};");
    }
}
