<?php

declare(strict_types=1);

namespace Thesis\Dic\Error;

use Typhoon\Type;

/**
 * @api
 */
final class UnsupportedBindingType extends ConfigurationError
{
    public function __construct(Type $type, ?\Throwable $previous = null)
    {
        parent::__construct(
            \sprintf('Type %s is not supported for binding', Type\stringify($type)),
            previous: $previous,
        );
    }
}
