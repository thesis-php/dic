<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Exception\BindingTypeNotSupported;
use Thesis\Dic\Ref;
use Typhoon\Type;
use function Thesis\Formatter\formatReflectedParameter;

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
     * @throws BindingTypeNotSupported
     */
    public function bind(Ref $ref, Type $type, string|\Stringable|\UnitEnum $qualifier): void
    {
        BindingTypeValidator::validate($type);

        $this->bindings[self::key($type, $qualifier)] = $ref;
    }

    /**
     * @return Ref<mixed>
     */
    public function autowire(\ReflectionParameter $parameter, string|\Stringable|\UnitEnum $qualifier): Ref
    {
        $type = TypeReflector::parameterType($parameter)
            ?? throw new \LogicException(\sprintf(
                'Parameter `%s` does not have a type to be autowired',
                formatReflectedParameter($parameter),
            ));

        return $this->bindings[self::key($type, $qualifier)]
            ?? throw new \LogicException(\sprintf(
                'No bound services match parameter `%s`',
                formatReflectedParameter($parameter),
            ));
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
