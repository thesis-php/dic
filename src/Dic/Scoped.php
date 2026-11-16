<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\NonCopyable;

/**
 * @api
 *
 * @template-covariant T
 */
final readonly class Scoped
{
    use NonCopyable;

    /**
     * @internal
     *
     * @param Ref<T> $ref
     */
    public function __construct(
        private Container $container,
        private Ref $ref,
    ) {}

    /**
     * Opens a fresh scope, resolves the wrapped service in it, runs $function and disposes the scope.
     *
     * @template R
     * @param callable(T): R $function
     * @return R
     */
    public function run(callable $function): mixed
    {
        $scope = $this->open();
        $error = null;

        try {
            return $function($scope->value);
        } catch (\Throwable $error) {
            throw $error;
        } finally {
            $scope->dispose($error);
        }
    }

    /**
     * Opens a fresh scope and resolves the wrapped service in it, like {@see self::run()},
     * but leaves the scope open until {@see ScopeHandle::dispose()} is called.
     * Use it when a unit of work does not fit into one callback: it starts in one call and ends in another.
     *
     * @return ScopeHandle<T>
     */
    public function open(): ScopeHandle
    {
        $scope = $this->container->startScope();

        try {
            $value = $scope->get($this->ref);
        } catch (\Throwable $error) {
            // If resolution fails halfway, the services already created in the scope are still disposed.
            $disposalErrors = $scope->dispose($error);

            if ($disposalErrors !== []) {
                throw new DisposalFailed($disposalErrors, $error);
            }

            throw $error;
        }

        return new ScopeHandle($scope, $value);
    }
}
