<?php

declare(strict_types=1);

use Capell\Admin\Actions\Widgets\ResolveBlockPickerMetadataAction;
use Capell\Admin\Contracts\Widgets\BlockPickerMetadataProvider;
use Capell\Admin\Data\Widgets\BlockPickerItemMetadataData;
use Capell\BlockLibrary\Support\BlockPickerMetadataAdapter;
use Capell\BlockLibrary\Support\DefaultBlockCatalog;

it('adapts every default block into Core\'s neutral block-picker metadata contract', function (): void {
    $metadata = (new BlockPickerMetadataAdapter)->blockPickerMetadata();

    expect($metadata)->toHaveCount(count(DefaultBlockCatalog::keys()));

    foreach (DefaultBlockCatalog::keys() as $key) {
        expect($metadata)->toHaveKey($key);

        $item = $metadata[$key];

        expect($item)->toBeInstanceOf(BlockPickerItemMetadataData::class)
            ->and($item->label)->toBe(DefaultBlockCatalog::label($key))
            ->and($item->description)->toBe(DefaultBlockCatalog::description($key))
            ->and($item->category)->toBe('foundation')
            ->and($item->icon)->toBe(DefaultBlockCatalog::icon($key)->value)
            ->and($item->searchTerms)->toBe(DefaultBlockCatalog::searchTerms($key));
    }
});

it('registers the adapter against Core\'s block-picker metadata contract on boot', function (): void {
    $providers = collect(app()->tagged(BlockPickerMetadataProvider::TAG));

    expect($providers->contains(fn (object $provider): bool => $provider instanceof BlockPickerMetadataAdapter))->toBeTrue();
});

it('is resolved end-to-end through Core\'s neutral metadata action', function (): void {
    $metadata = ResolveBlockPickerMetadataAction::run();

    foreach (DefaultBlockCatalog::keys() as $key) {
        expect($metadata)->toHaveKey($key)
            ->and($metadata[$key]->label)->toBe(DefaultBlockCatalog::label($key));
    }
});
