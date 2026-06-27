<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Container;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Ref;

/**
 * @internal
 */
final readonly class Singletons extends Container
{
    public function __construct(
        private Factories $singletonFactories,
        private Factories $scopedFactories,
        Disposers $disposers,
    ) {
        parent::__construct($disposers);
    }

    public function get(Ref $ref): mixed
    {
        if ($this->values->offsetExists($ref)) {
            return $this->values->offsetGet($ref);
        }

        $value = $this->singletonFactories->create($ref, $this);
        $this->values->offsetSet($ref, $value);

        return $value;
    }

    public function startScope(): Scope
    {
        return new Scope(
            singletons: $this,
            factories: $this->scopedFactories,
            disposers: $this->disposers,
        );
    }
}
