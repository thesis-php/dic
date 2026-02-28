<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\Internal\Autowiring\MatchBindingType;
use Thesis\DIC\Ref;
use Typhoon\Type;

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
     * @template T
     * @param Type<T> $type
     * @return list<Ref<T>>
     */
    public function autowire(Type $type, string|\Stringable|\UnitEnum $qualifier): array
    {
        /** @var list<Ref<T>> */
        $candidates = array_unique(
            array_column(
                array_filter(
                    $this->bindingsByQualifier[self::stringifyQualifier($qualifier)] ?? [],
                    static fn(Binding $binding) => $type->accept(new MatchBindingType($binding->type)),
                ),
                'ref',
            ),
            SORT_REGULAR,
        );

        if ($candidates === [] && $this->parent !== null) {
            return $this->parent->autowire($type, $qualifier);
        }

        return $candidates;
    }

    private static function stringifyQualifier(string|\Stringable|\UnitEnum $qualifier): string
    {
        if ($qualifier instanceof \UnitEnum) {
            return \sprintf('%s::%s', $qualifier::class, $qualifier->name);
        }

        return (string) $qualifier;
    }
}
