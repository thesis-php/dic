<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Lifetime;
use Thesis\Dic\Ref;

/**
 * @internal
 */
final readonly class Scope implements Container
{
    /**
     * @var \WeakMap<Ref<mixed>, mixed>
     */
    private \WeakMap $scopeds;

    public function __construct(
        private Root $root,
        private Factories $factories,
        private Disposers $disposers,
    ) {
        $this->scopeds = new \WeakMap();
    }

    public function get(Ref $ref): mixed
    {
        if ($ref->lifetime === Lifetime::Singleton) {
            return $this->root->get($ref);
        }

        try {
            return $this->scopeds->offsetGet($ref);
        } catch (\Error) {
            $value = $this->factories->create($ref, $this);
            $this->scopeds->offsetSet($ref, $value);

            return $value;
        }
    }

    public function startScope(): self
    {
        return new self(
            root: $this->root,
            factories: $this->factories,
            disposers: $this->disposers,
        );
    }

    public function dispose(?\Throwable $error = null): void
    {
        foreach ($this->scopeds as $ref => $value) {
            $this->disposers->dispose($ref, $value, $error);
            $this->scopeds->offsetUnset($ref);
        }
    }
}
