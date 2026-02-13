<?php

declare(strict_types=1);

namespace Thesis\DIC;

use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\Binding;
use Thesis\DIC\Internal\Tagger;
use Typhoon\Type;
use function Thesis\DIC\Internal\Type\nativeTypeOf;

/**
 * @api
 *
 * @template T
 * @implements Reference<T>
 */
abstract class Configurator implements Reference
{
    /**
     * @template V
     * @param Reference<V> $reference
     * @return ?Type<contravariant V>
     */
    final public static function referenceType(Reference $reference): ?Type
    {
        if ($reference instanceof self) {
            return $reference->nativeType;
        }

        if ($reference instanceof Value) {
            return nativeTypeOf($reference->value);
        }

        return null;
    }

    /**
     * @internal
     *
     * @param ?Type<contravariant T> $nativeType
     */
    protected function __construct(
        protected readonly ?Type $nativeType,
        public readonly Location $declaredAt,
        protected readonly Autowiring $autowiring,
        private readonly Tagger $tagger,
    ) {}

    /**
     * @param ?Type<contravariant T> $type
     */
    final public function bind(?Type $type = null, string|\Stringable|\UnitEnum $qualifier = ''): static
    {
        $this->autowiring->addBinding(
            new Binding(
                reference: $this,
                type: $type ?? $this->nativeType ?? throw new \LogicException(),
                qualifier: $qualifier,
            ),
        );

        return $this;
    }

    /**
     * @param Tag<T> $tag
     */
    final public function tag(Tag $tag): static
    {
        $this->tagger->tag($this, $tag);

        return $this;
    }

    public function __toString(): string
    {
        return "[service at {$this->declaredAt}]";
    }
}
