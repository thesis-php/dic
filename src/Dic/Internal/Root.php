<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Ref;

/**
 * @internal
 */
final readonly class Root implements Container
{
    /**
     * @var \WeakMap<Ref<mixed>, mixed>
     */
    private \WeakMap $singletons;

    public function __construct(
        private Factories $singletonFactories,
        private Factories $scopedFactories,
        private Disposers $disposers,
    ) {
        $this->singletons = new \WeakMap();
    }

    public function get(Ref $ref): mixed
    {
        try {
            return $this->singletons->offsetGet($ref);
        } catch (\Error) {
            $value = $this->singletonFactories->create($ref, $this);
            $this->singletons->offsetSet($ref, $value);
            $this->singletonFactories->remove($ref);

            return $value;
        }
    }

    public function startScope(): Scope
    {
        return new Scope(
            root: $this,
            factories: $this->scopedFactories,
            disposers: $this->disposers,
        );
    }

    public function dispose(?\Throwable $error): void
    {
        foreach (clone $this->singletons as $ref => $value) {
            $this->disposers->dispose($ref, $value, $error);
            $this->singletons->offsetUnset($ref);
        }
    }
}
