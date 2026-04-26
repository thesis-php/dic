<?php

declare(strict_types=1);

namespace Thesis\Dic\Exception;

use Thesis\Dic\Ref;

/**
 * @api
 */
final class ContainerAlreadyBuilt extends \LogicException
{
    /**
     * @param Ref<*> $ref
     */
    public function __construct(Ref $ref)
    {
        parent::__construct("Cannot configure {$ref} after container is built");
    }
}
