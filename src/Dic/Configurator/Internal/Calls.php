<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator\Internal;

/**
 * @internal
 *
 * @require-extends Ref<*>
 */
trait Calls
{
    /**
     * @var list<Call>
     */
    private array $calls = [];

    /**
     * @param array<mixed> $args
     */
    final public function call(string $method, array $args = []): static
    {
        $this->ensureConfigurable();

        $this->calls[] = new Call(
            method: $method,
            arguments: new Arguments(
                function: $this->internalReflection->publicMethod($method),
                values: $args,
            ),
        );

        return $this;
    }

    /**
     * @param array<mixed> $args
     */
    final public function chainCall(string $method, array $args = []): static
    {
        $this->ensureConfigurable();

        $this->calls[] = new Call(
            method: $method,
            arguments: new Arguments(
                function: $this->internalReflection->publicMethod($method),
                values: $args,
            ),
            chain: true,
        );

        return $this;
    }
}
