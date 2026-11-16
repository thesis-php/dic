<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\NonCopyable;

/**
 * A scope opened by {@see Scoped::open()} and kept open until {@see self::dispose()}.
 *
 * @api
 *
 * @template-covariant T
 */
final class ScopeHandle
{
    use NonCopyable;

    /**
     * @internal
     *
     * @param T $value
     */
    public function __construct(
        private ?Container $scope,
        public readonly mixed $value,
    ) {}

    /**
     * Disposes the scope; $error is passed to the disposers as the error in flight.
     * Only the first call disposes, the next ones do nothing.
     */
    public function dispose(?\Throwable $error = null): void
    {
        $scope = $this->scope;

        if ($scope === null) {
            return;
        }

        // Cleared before disposing: a second call, even from another fiber while a disposer suspends this one, does nothing.
        $this->scope = null;
        $disposalErrors = $scope->dispose($error);

        if ($disposalErrors !== []) {
            throw new DisposalFailed($disposalErrors, $error);
        }
    }
}
