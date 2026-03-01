<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\Internal\AutowireableFactory\Parameter;
use Thesis\DIC\Internal\Autowiring\MatchBindingType;
use const Typhoon\Type\mixedT;

/**
 * @internal
 */
final class Autowiring
{
    private ?self $parent = null;

    public function inheritAutowiringFrom(self $autowiring): void
    {
        $this->parent = $autowiring;
    }

    /**
     * @var array<string, non-empty-list<Binding<*>>>
     */
    private array $bindingsByQualifier = [];

    /**
     * @param Binding<*> $binding
     */
    public function addBinding(Binding $binding): void
    {
        $this->bindingsByQualifier[self::stringifyQualifier($binding->qualifier)][] = $binding;
    }

    /**
     * @return list<mixed>
     */
    public function autowire(Parameter $parameter): array
    {
        $qualifier = $parameter->qualifier;
        $type = $parameter->type ?? mixedT;

        $candidates = array_unique(
            array_column(
                array_filter(
                    $this->bindingsByQualifier[self::stringifyQualifier($qualifier)] ?? [],
                    static fn(Binding $binding) => $type->accept(new MatchBindingType($binding->type)),
                ),
                'value',
            ),
            SORT_REGULAR,
        );

        if ($candidates === [] && $this->parent !== null) {
            return $this->parent->autowire($parameter);
        }

        return array_values($candidates);
    }

    private static function stringifyQualifier(string|\Stringable|\UnitEnum $qualifier): string
    {
        if ($qualifier instanceof \UnitEnum) {
            return \sprintf('%s::%s', $qualifier::class, $qualifier->name);
        }

        return (string) $qualifier;
    }
}
