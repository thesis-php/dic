<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\Qualifier;
use Typhoon\Type;

/**
 * @internal
 *
 * @template T
 */
final readonly class ParameterSignature
{
    /**
     * @var non-empty-string
     */
    private string $type;

    /**
     * @param Type<contravariant T> $type
     * @param null|non-empty-string|\UnitEnum $qualifier
     * @param ?non-empty-string $name
     */
    public function __construct(
        Type $type,
        private null|string|\UnitEnum $qualifier,
        private ?string $name,
    ) {
        $this->type = Type\stringify($type);
    }

    public function matches(\ReflectionParameter $parameter): bool
    {
        return $this->typeMatches($parameter->getType())
            && $this->qualifierMatches($parameter)
            && $this->nameMatches($parameter->getName());
    }

    private function typeMatches(?\ReflectionType $type): bool
    {
        if ($type instanceof \ReflectionNamedType) {
            return $type->getName() === $this->type;
        }

        if ($type instanceof \ReflectionUnionType) {
            return array_any($type->getTypes(), $this->typeMatches(...));
        }

        // todo intersection
        return false;
    }

    private function qualifierMatches(\ReflectionParameter $parameter): bool
    {
        $attribute = $parameter->getAttributes(Qualifier::class)[0] ?? null;

        return $attribute?->newInstance()?->qualifier === $this->qualifier;
    }

    /**
     * @param non-empty-string $name
     */
    private function nameMatches(string $name): bool
    {
        return $this->name === null || $this->name === $name;
    }
}
