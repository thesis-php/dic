<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;
use Thesis\Dic\BuildError;
use Thesis\Dic\TaggedRefs;
use Thesis\Fixture\ApcuCache;
use Thesis\Fixture\CacheTag;
use Thesis\Fixture\PriorityTag;
use Thesis\Fixture\RedisCache;
use function Thesis\Fixture\ref;

#[Test]
#[Covers(Tags::class)]
final class TagsTest
{
    public function onResolveListenerReceivesCollectedTags(): void
    {
        $tags = new Tags();
        $tags->add(ref(new ApcuCache()), new CacheTag());

        $found = null;
        $tags->onResolution(static function (TaggedRefs $taggedRefs) use (&$found): void {
            $found = $taggedRefs->find(CacheTag::class);
        });

        $tags->resolve();

        Assert::notNull($found);
        Assert::count($found, 1);
    }

    public function addingTagAfterResolveIsRejected(): void
    {
        $tags = new Tags();
        $tags->resolve();

        Expect::exception(BuildError::class);

        $tags->add(ref(new ApcuCache()), new CacheTag());
    }

    public function addingAlreadyRequestedTagDuringResolutionIsRejected(): void
    {
        $tags = new Tags();

        $tags->onResolution(static function (TaggedRefs $taggedRefs) use ($tags): void {
            $taggedRefs->find(CacheTag::class);

            $tags->add(ref(new ApcuCache()), new CacheTag());
        });

        Expect::exception(BuildError::class);

        $tags->resolve();
    }

    public function addingNotYetRequestedTagDuringResolutionIsAllowed(): void
    {
        $tags = new Tags();

        $tags->onResolution(static function (TaggedRefs $_) use ($tags): void {
            $tags->add(ref(new RedisCache()), new PriorityTag(1));
        });

        $found = null;
        $tags->onResolution(static function (TaggedRefs $taggedRefs) use (&$found): void {
            $found = $taggedRefs->find(PriorityTag::class);
        });

        $tags->resolve();

        Assert::notNull($found);
        Assert::count($found, 1);
    }

    public function listeningAfterResolveIsRejected(): void
    {
        $tags = new Tags();
        $tags->resolve();

        Expect::exception(BuildError::class);

        $tags->onResolution(static function (TaggedRefs $_): void {});
    }
}
