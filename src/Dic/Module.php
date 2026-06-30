<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Thesis\Dic;

/**
 * @api
 *
 * @template-covariant T
 */
interface Module
{
    /**
     * @return T
     */
    public function configure(Dic $dic): mixed;
}
