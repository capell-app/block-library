<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use Capell\Core\Models\Contracts\Translatable;
use Capell\Core\Models\Media as CoreMedia;
use Capell\Core\Models\Translation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

final class ResolveBuilderBlockMediaAction
{
    use AsFake;
    use AsObject;

    public static function collection(mixed $key): ?string
    {
        return is_string($key) && preg_match('/^[a-f0-9-]{36}$/D', $key) === 1 ? 'builder-block-' . $key : null;
    }

    /** @return array<string, array{url: string, alt: ?string, width?: int, height?: int}> */
    public function handle(mixed $owner, mixed $key): array
    {
        $collection = self::collection($key);
        if ($collection === null) {
            return [];
        }

        if ($owner instanceof Model && $owner instanceof Translatable) {
            $owner->loadMissing('translation');
            $owner = $owner->getRelation('translation');
        }

        if (! $owner instanceof Translation) {
            return [];
        }

        // Every language of the record shares the block's media; alt text is still read for this translation's language.
        $media = $owner->mediaOwner()->getMedia($collection);
        new Collection($media->filter(static fn (mixed $item): bool => $item instanceof CoreMedia)->all())->loadMissing('translations.language');
        $images = [];
        foreach ($media as $image) {
            if (! $image instanceof SpatieMedia) {
                continue;
            }

            if (! is_string($image->uuid) || ! str_starts_with((string) $image->mime_type, 'image/') || Config::get('filesystems.disks.' . $image->disk . '.visibility') !== 'public') {
                continue;
            }

            $url = $image->getUrl();
            if (BuildBuilderBlockRenderDataAction::make()->url($url) === '') {
                continue;
            }

            $metadata = $image instanceof CoreMedia ? $image->localizedMetadata($owner->language_id) : null;
            if ($image instanceof CoreMedia && ($metadata?->languageId === null || (! $metadata->decorative && trim((string) $metadata->alt) === ''))) {
                $metadata = $image->localizedMetadata();
            }

            $alt = $metadata?->decorative ? '' : ($metadata?->alt ?: null);
            $width = $image instanceof CoreMedia ? $image->getWidth() : null;
            $height = $image instanceof CoreMedia ? $image->getHeight() : null;
            $images[$image->uuid] = ['url' => $url, 'alt' => $alt] + ($width !== null && $height !== null && $width > 0 && $height > 0 ? ['width' => $width, 'height' => $height] : []);
        }

        return $images;
    }
}
