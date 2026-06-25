<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Container;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\NonCopyable;
use Thesis\Dic\Ref;

/**
 * @internal
 */
final readonly class Singletons implements Container
{
    use NonCopyable;

    /**
     * @var \SplObjectStorage<Ref<mixed>, mixed>
     */
    private \SplObjectStorage $values;

    public function __construct(
        private Factories $singletonFactories,
        private Factories $scopedFactories,
        private Disposers $disposers,
    ) {
        $this->values = new \SplObjectStorage();
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

    public function dispose(?\Throwable $error): void
    {
        foreach (clone $this->values as $ref) {
            $this->disposers->dispose($ref, $this->values[$ref], $error);
            $this->values->offsetUnset($ref);
        }
    }
}
