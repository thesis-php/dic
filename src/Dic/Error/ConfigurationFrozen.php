<?php

declare(strict_types=1);

namespace Thesis\Dic\Error;

use Thesis\Dic\Ref;

/**
 * @api
 */
final class ConfigurationFrozen extends ConfigurationError
{
    /**
     * @param ?Ref<mixed> $ref
     */
    public function __construct(?Ref $ref = null)
    {
        parent::__construct(
            $ref === null
            ? 'Cannot configure the container: configuration is frozen once it starts building'
            : "Cannot configure {$ref}: configuration is frozen once the container starts building",
        );
    }
}
