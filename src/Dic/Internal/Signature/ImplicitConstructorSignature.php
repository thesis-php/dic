<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Signature;

use Thesis\Dic\Internal\Signature;

/**
 * @internal
 */
final class ImplicitConstructorSignature extends Signature
{
    public null $reflection { get => null; }

    public null $autowiringMode { get => null; }

    public array $parameters { get => []; }

    protected function __construct() {}
}
