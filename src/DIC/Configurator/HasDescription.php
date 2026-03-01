<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

/**
 * @api
 */
trait HasDescription
{
    /**
     * @var non-empty-string
     */
    private readonly string $description;

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->description;
    }
}
