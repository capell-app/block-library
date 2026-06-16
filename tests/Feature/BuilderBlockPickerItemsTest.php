<?php

declare(strict_types=1);

use Capell\BlockLibrary\Actions\ListBuilderBlockPickerItemsAction;
use Capell\BlockLibrary\Support\DefaultBlockCatalog;

it('lists searchable picker metadata for every default builder block', function (): void {
    $items = ListBuilderBlockPickerItemsAction::run();

    expect($items)->toHaveCount(count(DefaultBlockCatalog::keys()));

    $itemsByKey = collect($items)->keyBy('key');

    foreach (DefaultBlockCatalog::keys() as $key) {
        $item = $itemsByKey->get($key);

        expect($item)->not->toBeNull()
            ->and($item->label)->toBe(DefaultBlockCatalog::label($key))
            ->and($item->description)->toBe(DefaultBlockCatalog::description($key))
            ->and($item->category)->toBe('foundation')
            ->and($item->icon)->toBe(DefaultBlockCatalog::icon($key)->value)
            ->and($item->searchTerms)->toContain($key)
            ->and($item->searchTerms)->toContain('content block')
            ->and(class_exists($item->builderBlockClass))->toBeTrue();
    }
});

it('filters builder block picker metadata by catalog search terms', function (): void {
    $items = ListBuilderBlockPickerItemsAction::run('cta');

    expect(collect($items)->pluck('key')->all())->toBe(['call_to_action']);
});
