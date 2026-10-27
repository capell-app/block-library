<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Tests\Fixtures;

use Illuminate\Support\Facades\Config;
use Override;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

final class BuilderBlockTestUrlGenerator extends DefaultUrlGenerator
{
    #[Override]
    public function getUrl(): string
    {
        $url = Config::get('testing.builder_block_media_url');

        return $this->media instanceof Media && str_starts_with($this->media->collection_name, 'builder-block-') && is_string($url) ? $url : parent::getUrl();
    }
}
