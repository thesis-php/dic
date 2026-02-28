<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\AutowirableFunction\Argument;

use Thesis\DIC\Internal\AutowirableFunction\Argument;
use Thesis\DIC\Internal\AutowirableFunction\Parameter;
use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Container;
use Thesis\DIC\Ref;
use const Typhoon\Type\mixedT;

/**
 * @internal
 */
final readonly class Autowired extends Argument
{
    /**
     * @param list<Ref<mixed>> $candidates
     */
    protected function __construct(
        Parameter $parameter,
        private array $candidates = [],
    ) {
        parent::__construct($parameter);
    }

    public function autowire(Autowiring $autowiring): static
    {
        return new self(
            parameter: $this->parameter,
            candidates: $autowiring->autowire(
                type: $this->parameter->type ?? mixedT,
                qualifier: $this->parameter->qualifier,
            ),
        );
    }

    public function check(): void
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
            return fn(Container $container) => $container->get($this->candidates[0]);
        }

        if ($this->candidates === []) {
            if ($this->parameter->hasDefault) {
                return fn() => $this->parameter->default;
            }

            throw new \LogicException(\sprintf(
                'No autowiring candidates for `%s`',
                $this->parameter->formattedNameWithQualifierAndType,
            ));
        }

        throw new \LogicException(\sprintf(
            "Autowiring of `%s` is ambiguous:\n- %s",
            $this->parameter->formattedNameWithQualifierAndType,
            implode("\n- ", $this->candidates),
        ));
    }
}
