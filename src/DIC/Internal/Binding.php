<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\Internal\Autowiring\ValidateBindingType;
use Thesis\DIC\Ref;
use Typhoon\Type;

/**
 * @internal
 *
 * @template T
 */
final readonly class Binding
{
    /**
     * @param Type<contravariant T> $type
     * @param T|Ref<T> $value
     */
    public function __construct(
        public Type $type,
        public string|\Stringable|\UnitEnum $qualifier,
        public mixed $value,
    ) {
        if (!$type->accept(new ValidateBindingType())) {
            throw new \LogicException(\sprintf(
                'Type `%s` is not supported for binding',
                Type\stringify($type),
            ));
        }
    }
}
