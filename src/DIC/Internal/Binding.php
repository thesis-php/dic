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
final class Binding
{
    /**
     * @param Ref<T> $ref
     * @param Type<contravariant T> $type
     */
    public function __construct(
        public Ref $ref,
        public Type $type,
        public string|\Stringable|\UnitEnum $qualifier,
    ) {
        if (!$type->accept(new ValidateBindingType())) {
            throw new \LogicException(\sprintf(
                'Type `%s` is not supported for binding',
                Type\stringify($type),
            ));
        }
    }
}
