<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use Capell\Admin\Filament\Components\Forms\Editor\ContentBuilder;
use Capell\Core\Models\Media;
use Capell\Core\Models\Translation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use LogicException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

/** Runs after Core clones and regenerates widget identities, before another uploader can delete media. */
final class SeparateClonedBuilderBlockMediaAction
{
    use AsFake;
    use AsObject;

    public function handle(ContentBuilder $component): void
    {
        $state = $component->getRawState();
        $owner = $component->getRecord();
        if (! is_array($state) || ! $owner instanceof Translation) {
            return;
        }

        $seen = [];
        $stagedCollections = [];
        try {
            foreach ($state as $index => $block) {
                if (! is_array($block) || ! in_array($block['type'] ?? null, ['logos', 'team'], true) || ! is_array($block['data'] ?? null)) {
                    continue;
                }

                $key = $block['data']['media_key'] ?? null;
                $collection = ResolveBuilderBlockMediaAction::collection($key);
                if ($collection === null) {
                    continue;
                }

                if (! isset($seen[$collection])) {
                    $seen[$collection] = true;

                    continue;
                }

                $newKey = $this->unusedKey($owner);
                $stagedCollections[] = 'builder-block-' . $newKey;
                $references = [];
                foreach ($owner->getMedia($collection) as $image) {
                    $copy = $image->copy($owner, 'builder-block-' . $newKey, $image->disk);
                    $references[$image->uuid] = $copy->uuid;
                    if ($image instanceof Media && $copy instanceof Media) {
                        $image->loadMissing('translations');
                        $translations = $image->getRelation('translations');
                        foreach ($translations instanceof Collection ? $translations : [] as $translation) {
                            if ($translation instanceof Translation) {
                                $copy->translations()->save($translation->replicate());
                            }
                        }
                    }
                }

                $data = $block['data'];
                array_walk_recursive($data, static function (mixed &$value) use ($references): void {
                    if (is_string($value) && isset($references[$value])) {
                        $value = $references[$value];
                    }
                });
                if (is_array($data['block_images'] ?? null)) {
                    $uploads = [];
                    foreach ($data['block_images'] as $uploadKey => $value) {
                        $uploads[$references[$uploadKey] ?? $uploadKey] = $value;
                    }

                    $data['block_images'] = $uploads;
                }

                $data['media_key'] = $newKey;
                $block['data'] = $data;
                $state[$index] = $block;
            }

            $component->rawState($state);
        } catch (Throwable $throwable) {
            // A database rollback cannot remove physical copies. Include a copy
            // persisted by Spatie even if a later step threw before it returned.
            foreach ($owner->media()->whereIn('collection_name', $stagedCollections)->get() as $copy) {
                if ($copy instanceof Media) {
                    foreach ($copy->translations()->get() as $metadata) {
                        $metadata->forceDelete();
                    }
                }

                $copy->forceDelete();
            }

            throw $throwable;
        } finally {
            $owner->unsetRelation('media');
        }
    }

    /**
     * Compensation removes everything in a staged collection, so a target
     * collection must be provably unused before this invocation owns it.
     */
    private function unusedKey(Translation $owner): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $key = (string) Str::uuid();

            if (! $owner->media()->where('collection_name', 'builder-block-' . $key)->exists()) {
                return $key;
            }
        }

        throw new LogicException('Could not allocate an unused builder block media collection.');
    }
}
