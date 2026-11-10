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
     * @template R
     * @param callable(T): R $function
     * @return R
     */
    public function run(callable $function): mixed
    {
        $scope = $this->container->startScope();
        $error = null;

        try {
            // Resolved inside the try: if it fails halfway, the services already created in the scope are still disposed.
            return $function($scope->get($this->ref));
        } catch (\Throwable $error) {
            throw $error;
        } finally {
            $disposalErrors = $scope->dispose($error);

            if ($disposalErrors !== []) {
                throw new DisposalFailed($disposalErrors, $error);
            }
        }
    }
}
