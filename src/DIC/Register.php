<?php

declare(strict_types=1);

namespace Thesis\DIC;

use Thesis\DIC\Internal\Autowiring;
use Thesis\DIC\Internal\TaggedValues;
use Typhoon\Type;

/**
 * @api
 *
 * @template T
 */
final readonly class Register
{
    /**
     * @var T
     */
    public mixed $value;

    /**
     * @internal
     *
     * @param T $value
     */
    public function __construct(
        mixed $value,
        private Autowiring $autowiring,
        private TaggedValues $taggedValues,
    ) {
        $this->value = $value;
    }

    /**
     * @param ?Type<contravariant T> $type if null, value's type T is used for autowiring
     * @param null|non-empty-string|\UnitEnum $qualifier if null, parameter should have no qualifier to be autowired
     * @param ?non-empty-string $name if null, parameter's name is not used for autowiring
     */
    public function bind(?Type $type = null, null|string|\UnitEnum $qualifier = null, ?string $name = null): static
    {
        $this->autowiring->bind(
            value: $this->value,
            type: $type,
            qualifier: $qualifier,
            name: $name,
        );

        return $this;
    }

    /**
     * @param Tag<T> $tag
     */
    public function tag(Tag $tag): static
    {
        $this->taggedValues->register($this->value, $tag);

        return $this;
    }
}
