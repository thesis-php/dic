<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Exception\UnsupportedType;
use Thesis\Dic\Ref;
use Typhoon\Type;

/**
 * @internal
 */
final class Autowiring
{
    /**
     * @var array<non-empty-string, Ref<mixed>>
     */
    private array $bindings = [];

    /**
     * @template T
     * @param Type<contravariant T> $type
     * @param Ref<T> $ref
     * @throws UnsupportedType
     */
    public function bind(Ref $ref, Type $type, string|\Stringable|\UnitEnum $qualifier): void
    {
        TypeValidator::validate($type);

        $this->bindings[self::key($type, $qualifier)] = $ref;
    }

    /**
     * @return ?Ref<mixed>
     */
    public function autowire(Type $type, string|\Stringable|\UnitEnum $qualifier): ?Ref
    {
        return $this->bindings[self::key($type, $qualifier)] ?? null;
    }

    /**
     * @return non-empty-string
     */
    private static function key(Type $type, string|\Stringable|\UnitEnum $qualifier): string
    {
        return Type\stringify($type) . '.' . match (true) {
            $qualifier instanceof \UnitEnum => \sprintf('%s::%s', $qualifier::class, $qualifier->name),
            default => (string) $qualifier,
        };
    }
}
