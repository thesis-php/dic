<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Lifetime;
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
        private Factories $factories,
        private Disposers $disposers,
    ) {
        $this->singletons = new \WeakMap();
    }

    public function get(Ref $ref): mixed
    {
        if ($ref->lifetime !== Lifetime::Singleton) {
            throw new \LogicException("{$ref->lifetime->name} service {$ref} must not be requested from the root container");
        }

        try {
            return $this->singletons->offsetGet($ref);
        } catch (\Error) {
            $value = $this->factories->create($ref, $this);
            $this->singletons->offsetSet($ref, $value);
            $this->factories->remove($ref);

            return $value;
        }
    }

    public function startScope(): Scope
    {
        return new Scope(
            root: $this,
            factories: $this->factories,
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
