<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Container;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Ref;

/**
 * @internal
 */
final readonly class Scope extends Container
{
    public function __construct(
        private Singletons $singletons,
        private Factories $factories,
        Disposers $disposers,
    ) {
        parent::__construct($disposers);
    }

    public function get(Ref $ref): mixed
    {
        if (!$this->factories->has($ref)) {
            return $this->singletons->get($ref);
        }

        if ($this->values->offsetExists($ref)) {
            return $this->values->offsetGet($ref);
        }

        $value = $this->factories->create($ref, $this);
        $this->values->offsetSet($ref, $value);

        return $value;
    }

    public function startScope(): self
    {
        return new self(
            singletons: $this->singletons,
            factories: $this->factories,
            disposers: $this->disposers,
        );
    }
}
