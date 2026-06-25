<?php

declare(strict_types=1);

namespace Thesis\Dic\Error;

use Thesis\Dic\Ref;

/**
 * @api
 */
final class InvalidConfigurationError extends ConfigurationError
{
    /**
     * @param Ref<mixed> $ref
     */
    public function __construct(Ref $ref, \Throwable $previous)
    {
        $detail = $previous->getMessage();

        parent::__construct(
            message: $detail === ''
                ? "Invalid configuration for {$ref}"
                : "Invalid configuration for {$ref}: {$detail}",
            previous: $previous,
        );
    }
}
