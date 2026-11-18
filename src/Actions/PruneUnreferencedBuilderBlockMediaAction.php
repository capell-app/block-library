<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use Capell\Core\Models\Media;
use Capell\Core\Models\Translation;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use WeakMap;

/**
 * Removes builder block media once no saved logos or team block refers to its
 * collection. It reads the persisted content, never form state, and it only
 * ever touches `builder-block-{uuid}` collections owned by the given Translation's media owner, checked against
 * the saved content of every translation that shares it.
 *
 * Without candidates it removes every unreferenced collection. An editor save passes the collections that its
 * previously saved content referred to, so it only removes what that save dropped: a block another editor has
 * cloned but not saved yet has media that no saved content has ever referenced, and an unrelated save must not
 * delete it.
 */
final class PruneUnreferencedBuilderBlockMediaAction
{
    use AsFake;
    use AsObject;

    private const string COLLECTION_PATTERN = '/^builder-block-[a-f0-9-]{36}$/D';

    /** @var WeakMap<Translation, array<string, true>>|null */
    private static ?WeakMap $savedReferences = null;

    /**
     * Records the collections the translation's saved content refers to. Call it while a content update is
     * pending, so the original attributes are still what was saved.
     */
    public static function rememberSavedReferences(Translation $translation): void
    {
        $original = $translation->getRawOriginal('content');
        $content = is_string($original) ? json_decode($original, true) : null;

        self::$savedReferences ??= new WeakMap;
        self::$savedReferences[$translation] = is_array($content) ? self::collectionsIn($content) : [];
    }

    /**
     * The collections the translation's content referred to before its latest save, once; empty when that save
     * did not change the content.
     *
     * @return list<string>
     */
    public static function takeSavedReferences(Translation $translation): array
    {
        if (! self::$savedReferences instanceof WeakMap || ! isset(self::$savedReferences[$translation])) {
            return [];
        }

        $collections = array_keys(self::$savedReferences[$translation]);
        unset(self::$savedReferences[$translation]);

        return $collections;
    }

    /**
     * @param  list<string>|null  $candidates  Only these collections may be removed; null considers every one.
     * @return int The number of media items removed.
     */
    public function handle(Translation $owner, ?array $candidates = null): int
    {
        if (! $owner->exists || $candidates === []) {
            return 0;
        }

        // Every language of the record shares the block media, so a collection stays while any translation's saved
        // content still refers to it.
        $mediaOwner = $owner->mediaOwner();
        // Trashed translations count too: restoring one must not find its block media gone.
        $sources = $mediaOwner === $owner
            ? $owner->newQueryWithoutScopes()->whereKey($owner->getKey())
            : $owner->newQueryWithoutScopes()->where('translatable_type', $owner->translatable_type)->where('translatable_id', $owner->translatable_id);
        $referenced = [];

        // The content cast follows the page structure, so read the stored JSON itself.
        foreach ($sources->toBase()->pluck('content') as $stored) {
            if ($stored === null && $mediaOwner !== $owner) {
                continue;
            }

            $content = is_string($stored) ? json_decode($stored, true) : null;

            // Content that is not a block list (HTML or missing) cannot be trusted to
            // list every reference, so nothing is removed.
            if (! is_array($content)) {
                return 0;
            }

            $referenced += self::collectionsIn($content);
        }

        $allowed = $candidates === null ? null : array_fill_keys($candidates, true);
        $removed = 0;
        foreach ($mediaOwner->media()->where('collection_name', 'like', 'builder-block-%')->get() as $media) {
            if (preg_match(self::COLLECTION_PATTERN, $media->collection_name) !== 1 || isset($referenced[$media->collection_name])) {
                continue;
            }

            if ($allowed !== null && ! isset($allowed[$media->collection_name])) {
                continue;
            }

            if ($media instanceof Media) {
                foreach ($media->translations()->get() as $metadata) {
                    $metadata->forceDelete();
                }
            }

            $media->forceDelete();
            $removed++;
        }

        if ($removed > 0) {
            $mediaOwner->unsetRelation('media');
        }

        return $removed;
    }

    /**
     * @param  array<array-key, mixed>  $content
     * @return array<string, true>
     */
    private static function collectionsIn(array $content): array
    {
        $collections = [];

        foreach ($content as $block) {
            if (! is_array($block) || ! in_array($block['type'] ?? null, ['logos', 'team'], true) || ! is_array($block['data'] ?? null)) {
                continue;
            }

            $collection = ResolveBuilderBlockMediaAction::collection($block['data']['media_key'] ?? null);

            if ($collection !== null) {
                $collections[$collection] = true;
            }
        }

        return $collections;
    }
}
