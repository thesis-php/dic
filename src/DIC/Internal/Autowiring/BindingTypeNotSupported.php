<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Autowiring;

use Typhoon\Type;

final class BindingTypeNotSupported extends \LogicException
{
    public function __construct(string|Type $type)
    {
        parent::__construct(\sprintf(
            'Type `%s` is not supported as a binding type',
            \is_string($type) ? $type : Type\stringify($type),
        ));
    }
}
