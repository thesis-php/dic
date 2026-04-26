<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Internal\Factory\Closure;
use Thesis\Dic\Internal\Factory\ListOf;
use Thesis\Dic\Internal\Factory\Value;
use function Thesis\Formatter\formatReflectedParameter;

/**
 * @internal
 */
final readonly class ResolvedArguments
{
    /**
     * @param list<Factory|\ReflectionParameter> $list
     */
    public function __construct(
        public array $list,
    ) {}

    /**
     * @return Factory<list<mixed>>
     */
    public function toFactory(): Factory
    {
        if (array_all($this->list, static fn(mixed $a) => $a instanceof Value)) {
            return Value::from(array_column($this->list, 'value'));
        }

        return new ListOf(array_map(
            $this->argumentToFactory(...),
            $this->list,
        ));
    }

    private function argumentToFactory(Factory|\ReflectionParameter $argument): Factory
    {
        if (!$argument instanceof \ReflectionParameter) {
            return $argument;
        }

        if ($argument->isDefaultValueAvailable()) {
            return new Closure($argument->getDefaultValue(...));
        }

        throw new \LogicException(\sprintf(
            'Parameter %s is not autowired and has no default value',
            formatReflectedParameter($argument),
        ));
    }
}
