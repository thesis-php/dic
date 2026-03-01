<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowireableFactory\Argument;

use Thesis\DIC\Internal\AutowireableFactory\Argument;
use Thesis\DIC\Internal\AutowireableFactory\Parameter;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;
use Thesis\DIC\Ref;
use function Typhoon\Formatter\format;

/**
 * @internal
 */
final readonly class UndefinedAutowired extends Argument
{
    /**
     * @param list<mixed> $candidates
     */
    protected function __construct(
        Parameter $parameter,
        private array $candidates = [],
    ) {
        parent::__construct($parameter);
    }

    public function autowire(Autowiring $autowiring): static
    {
        $candidates = $autowiring->autowire($this->parameter);

        if ($candidates === []) {
            return $this;
        }

        return new self(
            parameter: $this->parameter,
            candidates: $candidates,
        );
    }

    public function ensureResolvable(): void
    {
        $this->resolver();
    }

    public function resolve(Container $container): mixed
    {
        return $this->resolver()($container);
    }

    /**
     * @return \Closure(Container): mixed
     */
    private function resolver(): \Closure
    {
        if (\count($this->candidates) === 1) {
            return fn(Container $container) => $container->resolve($this->candidates[0]);
        }

        if ($this->candidates === []) {
            if ($this->parameter->hasDefaultValue) {
                return fn() => $this->parameter->defaultValue;
            }

            throw new \LogicException(\sprintf(
                'No autowiring candidates for `%s`',
                $this->parameter->formattedNameWithQualifierAndType,
            ));
        }

        throw new \LogicException(\sprintf(
            "Autowiring of `%s` is ambiguous:\n- %s",
            $this->parameter->formattedNameWithQualifierAndType,
            implode("\n- ", array_map(
                static fn(mixed $candidate) => $candidate instanceof Ref ? (string) $candidate : format($candidate),
                $this->candidates,
            )),
        ));
    }
}
