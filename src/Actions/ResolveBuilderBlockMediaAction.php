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

final class ResolveBuilderBlockMediaAction
{
    use AsFake;
    use AsObject;

    public static function collection(mixed $key): ?string
    {
        return is_string($key) && preg_match('/^[a-f0-9-]{36}$/D', $key) === 1 ? 'builder-block-' . $key : null;
    }

    /** @return array<string, array{url: string, alt: ?string}> */
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

        $media = $owner->getMedia($collection);
        new Collection($media->filter(static fn (mixed $item): bool => $item instanceof CoreMedia)->all())->loadMissing('translations.language');
        $images = [];
        foreach ($media as $image) {
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
            $images[$image->uuid] = ['url' => $url, 'alt' => $alt];
        }

        return $images;
    }
}
