<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Container;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\NonCopyable;
use Thesis\Dic\Ref;

/**
 * @internal
 */
final readonly class Scope implements Container
{
    use NonCopyable;

    /**
     * @var \SplObjectStorage<Ref<mixed>, mixed>
     */
    private \SplObjectStorage $values;

    public function __construct(
        public Singletons $singletons,
        private Factories $factories,
        private Disposers $disposers,
    ) {
        $this->values = new \SplObjectStorage();
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

    public function dispose(?\Throwable $error): void
    {
        foreach (clone $this->values as $ref) {
            $this->disposers->dispose($ref, $this->values[$ref], $error);
            $this->values->offsetUnset($ref);
        }
    }
}
