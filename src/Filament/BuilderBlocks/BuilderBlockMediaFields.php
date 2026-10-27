<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Filament\BuilderBlocks;

use Capell\Admin\Actions\GetMaxUploadSizeInBytes;
use Capell\Admin\Contracts\Media\AdminMediaFieldFactory;
use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\BlockLibrary\Actions\ResolveBuilderBlockMediaAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/** The content Translation owns media, just as it does for Admin content widgets. */
final class BuilderBlockMediaFields
{
    /** @return array{Hidden, SpatieMediaLibraryFileUpload} */
    public static function uploads(): array
    {
        $upload = app()->bound(AdminMediaFieldFactory::class)
            ? MediaLibraryFileUpload::make('block_images')
            : SpatieMediaLibraryFileUpload::make('block_images');
        if (! $upload instanceof SpatieMediaLibraryFileUpload) {
            $upload = SpatieMediaLibraryFileUpload::make('block_images');
        }

        return [
            Hidden::make('media_key')->default(static fn (): string => (string) Str::uuid())
                ->afterStateHydrated(static function (Hidden $component, mixed $state): void {
                    if (ResolveBuilderBlockMediaAction::collection($state) === null) {
                        $component->state((string) Str::uuid());
                    }
                }),
            $upload
                ->label(__('capell-block-library::blocks.fields.block_images'))
                ->helperText(__('capell-block-library::blocks.fields.block_images_helper'))
                ->collection(static fn (Get $get): string => ResolveBuilderBlockMediaAction::collection($get('media_key')) ?? 'builder-block-unassigned')
                ->disk(static function (): string {
                    $disk = Config::get('media-library.disk_name');

                    return is_string($disk) && Config::get('filesystems.disks.' . $disk . '.visibility') === 'public' ? $disk : 'public';
                })
                ->maxSize((int) floor(GetMaxUploadSizeInBytes::run() / 1024))
                ->visibility('public')->image()->multiple()->live(),
        ];
    }

    public static function image(): Select
    {
        return Select::make('image')
            ->label(__('capell-block-library::blocks.fields.image'))
            ->helperText(__('capell-block-library::helpers.BuilderBlockMediaFields.image'))
            ->options(static function (?Model $record, Get $get): array {
                $images = ResolveBuilderBlockMediaAction::run($record, $get('../../media_key'));
                $options = [];
                foreach ($images as $reference => $image) {
                    $options[$reference] = $image['alt'] ?: basename($image['url']);
                }

                return $options;
            })
            ->selectablePlaceholder();
    }
}
