<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Factory;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Ref;
use Typhoon\Type\ClosureT;
use Typhoon\Type\Parameter;
use function Typhoon\Type\stringify;

/**
 * @internal
 *
 * @template-covariant T of \Closure
 * @implements Factory<T>
 */
final readonly class Signature implements Factory
{
    /**
     * @template S of \Closure
     * @param ClosureT<S> $signature
     * @param Ref<callable> $implementation
     * @param array<non-negative-int, Factory|Parameter> $arguments
     * @return self<S>
     */
    public static function from(
        ClosureT $signature,
        Ref $implementation,
        array $arguments,
    ): self {
        $printedParameters = [];
        /** @var \WeakMap<Parameter, non-empty-string> */
        $parameterVars = new \WeakMap();

        foreach ($signature->parameters as $position => $parameter) {
            $var = $parameter->name !== null ? "\${$parameter->name}" : "\$__p{$position}";
            $parameterVars[$parameter] = $var;
            $printedParameters[] = self::printParameter($parameter, $var);
        }

        $printedArguments = [];

        foreach ($arguments as $position => $argument) {
            if ($argument instanceof Parameter) {
                $printedArguments[] = $parameterVars[$argument];
            } else {
                $printedArguments[] = "\$__arguments[{$position}]";
            }
        }

        $printedParameters = implode(', ', $printedParameters);
        $printedArguments = implode(', ', $printedArguments);
        $printedReturnType = stringify($signature->returnType);
        $return = $printedReturnType === 'void' ? '' : 'return ';

        /** @var self<S> */
        return new self(
            function: $implementation,
            arguments: new Arguments(
                array_filter(
                    $arguments,
                    static fn(mixed $v) => $v instanceof Factory,
                ),
            ),
            code: <<<PHP
                return static function ({$printedParameters}) use(&\$__createFunction, &\$__createArguments): {$printedReturnType} {
                    static \$__function = \$__createFunction();
                    \$__createFunction = null;
                    
                    static \$__arguments = \$__createArguments();
                    \$__createArguments = null;

                    {$return}\$__function({$printedArguments});  
                };
                PHP,
        );
    }

    private static function printParameter(Parameter $parameter, string $var): string
    {
        $signature = stringify($parameter->type) . ' ';

        if ($parameter->isPassedByReference) {
            $signature .= '&';
        }

        if ($parameter->isVariadic) {
            $signature .= '...';
        }

        $signature .= $var;

        if ($parameter->hasDefault) {
            throw new \LogicException();
        }

        return $signature;
    }

    /**
     * @param Ref<callable> $function
     * @param non-empty-string $code
     */
    private function __construct(
        private Ref $function,
        private Arguments $arguments,
        private string $code,
    ) {}

    public function dependencies(): iterable
    {
        yield '' => $this->function;
        yield from $this->arguments->dependencies();
    }

    public function create(Container $container): mixed
    {
        $__createFunction = fn() => $container->get($this->function);
        $__createArguments = fn() => $this->arguments->create($container);

        /** @var T */
        return eval($this->code);
    }
}
