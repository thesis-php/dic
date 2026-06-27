<?php

declare(strict_types=1);

namespace Thesis\Dic;

/**
 * Thrown after teardown when one or more disposers failed.
 * Disposal is best-effort: every disposer still runs, and their failures are
 * collected here. If teardown was triggered by an error, that error is the
 * {@see self::getPrevious()}.
 *
 * @api
 */
final class DisposalFailed extends \RuntimeException
{
    /**
     * @param non-empty-list<\Throwable> $errors
     */
    public function __construct(
        public readonly array $errors,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            \sprintf('Disposal failed with %d error(s).', \count($errors)),
            previous: $previous ?? $errors[0],
        );
    }
}
