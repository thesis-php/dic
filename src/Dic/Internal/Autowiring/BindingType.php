<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Autowiring;

use Typhoon\Type;

/**
 * @internal
 *
 * @template T = mixed
 */
final readonly class BindingType
{
    /**
     * @template TT
     * @param Type<contravariant TT> $type
     * @return self<TT>
     * @throws UnsupportedBindingType
     */
    public static function ofTyphoonType(Type $type): self
    {
        /** @var self<TT> */
        return new self(BindingTypeStringifier::stringifyTyphoonType($type));
    }

    /**
     * @throws UnsupportedBindingType
     */
    public static function ofParameter(\ReflectionParameter $parameter): self
    {
        return new self(BindingTypeStringifier::stringifyParameterType($parameter));
    }

    /**
     * @param non-empty-lowercase-string $string
     */
    private function __construct(
        public string $string,
    ) {}

    public function equals(self $type): bool
    {
        return $this->string === $type->string;
    }
}
