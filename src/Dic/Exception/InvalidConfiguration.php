<?php

declare(strict_types=1);

namespace Thesis\Dic\Exception;

use Thesis\Dic\Ref;

/**
 * @api
 */
final class InvalidConfiguration extends \LogicException
{
    /**
     * @param Ref<*> $ref
     */
    public function __construct(Ref $ref, \Throwable $previous)
    {
        parent::__construct(
            message: "Invalid configuration for {$ref}. " . $previous->getMessage(),
            previous: $previous,
        );
    }
}
