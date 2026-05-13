<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator\Internal;

use Thesis\Dic\Internal\Arguments;
use function Thesis\Formatter\formatReflectedFunction;

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

        $reflection = $this->reflection->getMethod($method);

        if (!$reflection->isPublic()) {
            throw new \LogicException(\sprintf('`%s` must be public', formatReflectedFunction($reflection)));
        }

        $this->calls[] = new Call(
            method: $reflection->name,
            arguments: Arguments::forFunction($reflection, $args),
        );

        return $this;
    }

    /**
     * @param array<mixed> $args
     */
    final public function chainCall(string $method, array $args = []): static
    {
        $this->ensureConfigurable();

        $reflection = $this->reflection->getMethod($method);

        if (!$reflection->isPublic()) {
            throw new \LogicException(\sprintf('`%s` must be public', formatReflectedFunction($reflection)));
        }

        $this->calls[] = new Call(
            method: $reflection->name,
            arguments: Arguments::forFunction($reflection, $args),
            chain: true,
        );

        return $this;
    }
}
