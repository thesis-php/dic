<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\Closure;
use Thesis\Dic\Lifetime;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Thesis\Dic\Tag;
use Thesis\Dic\TaggedRef;
use Thesis\Dic\Tags;

/**
 * @api
 *
 * @template T
 * @template TTag of Tag<T>
 * @extends LifetimeConfigurator<list<T>>
 */
final class TaggedList extends LifetimeConfigurator
{
    /**
     * @var list<Ref<T>>
     */
    private array $refs = [];

    /**
     * @internal
     *
     * @param class-string<TTag>|TTag $tag
     * @param ?callable(TaggedRef<T, TTag>, TaggedRef<T, TTag>): int $sort
     */
    public function __construct(
        string|Tag $tag,
        ?callable $sort,
        Location $declaredAt,
        protected readonly Autowiring $autowiring,
        protected readonly ContainerBuilder $containerBuilder,
    ) {
        $containerBuilder->onResolveTags(function (Tags $tags) use ($tag, $sort): void {
            $taggedRefs = $tags->find($tag);

            if ($sort !== null) {
                usort($taggedRefs, $sort);
            }

            $this->refs = array_column($taggedRefs, 'ref');
        });

        parent::__construct(
            label: 'tagged ' . match (true) {
                \is_string($tag) => $tag,
                $tag instanceof \UnitEnum => \sprintf('%s::%s', $tag::class, $tag->name),
                default => $tag::class,
            },
            declaredAt: $declaredAt,
        );
    }

    protected function createFactory(): Factory
    {
        $refs = $this->refs;

        if ($this->lifetime === Lifetime::Singleton) {
            foreach ($refs as $ref) {
                if ($ref->lifetime === Lifetime::Scoped) {
                    throw new \LogicException("Cannot inject scoped service {$ref} into singleton {$this}");
                }
            }
        }

        return new Closure(static fn(Container $c) => array_map($c->get(...), $refs));
    }
}
