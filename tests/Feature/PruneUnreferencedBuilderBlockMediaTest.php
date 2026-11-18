<?php

declare(strict_types=1);

use Capell\Admin\Filament\Components\Forms\Editor\ContentBuilder;
use Capell\BlockLibrary\Actions\PruneUnreferencedBuilderBlockMediaAction;
use Capell\BlockLibrary\Actions\ResolveBuilderBlockMediaAction;
use Capell\Core\Models\Language;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\Tests\Support\SchemaStateHarness;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;

const PRUNE_KEEP_KEY = '11111111-1111-4111-8111-111111111111';
const PRUNE_DROP_KEY = '22222222-2222-4222-8222-222222222222';

/** @return list<string> */
function builderBlockCollections(Translation $translation): array
{
    $names = [];
    $owner = $translation->mediaOwner();
    foreach (Media::query()->where('model_type', $owner->getMorphClass())->where('model_id', $owner->getKey())->get() as $media) {
        $names[] = $media->collection_name;
    }

    sort($names);

    return $names;
}

/** @param  list<array<string, mixed>>  $content */
function translationWithBuilderMedia(array $content, string ...$keys): Translation
{
    $translation = Translation::factory()->translatable(Page::factory()->create())->create(['content' => $content]);
    foreach ($keys as $key) {
        $translation->mediaOwner()->addMedia(UploadedFile::fake()->image($key . '.png', 20, 20))->toMediaCollection('builder-block-' . $key);
    }

    return $translation->refresh();
}

it('removes the media of a logos block that is no longer in the saved content and keeps referenced media', function (): void {
    $translation = translationWithBuilderMedia([
        ['type' => 'logos', 'data' => ['media_key' => PRUNE_KEEP_KEY]],
        ['type' => 'content', 'data' => ['content' => '<p>Hello</p>']],
    ], PRUNE_KEEP_KEY, PRUNE_DROP_KEY);

    expect(PruneUnreferencedBuilderBlockMediaAction::run($translation))->toBe(1)
        ->and(builderBlockCollections($translation))->toBe(['builder-block-' . PRUNE_KEEP_KEY]);
});

it('treats team blocks as references and removes everything once the builder blocks are gone', function (): void {
    $translation = translationWithBuilderMedia([['type' => 'team', 'data' => ['media_key' => PRUNE_KEEP_KEY]]], PRUNE_KEEP_KEY);
    expect(PruneUnreferencedBuilderBlockMediaAction::run($translation))->toBe(0);

    $translation->update(['content' => [['type' => 'content', 'data' => ['content' => '']]]]);
    expect(PruneUnreferencedBuilderBlockMediaAction::run($translation))->toBe(1)
        ->and(builderBlockCollections($translation))->toBe([]);
});

it('never touches other collections or another owner\'s media', function (): void {
    $translation = translationWithBuilderMedia([['type' => 'content', 'data' => ['content' => '']]], PRUNE_DROP_KEY);
    $translation->mediaOwner()->addMedia(UploadedFile::fake()->image('hero.png', 20, 20))->toMediaCollection('image');
    $other = translationWithBuilderMedia([['type' => 'logos', 'data' => ['media_key' => PRUNE_KEEP_KEY]]], PRUNE_DROP_KEY);

    PruneUnreferencedBuilderBlockMediaAction::run($translation);

    expect(builderBlockCollections($translation))->toBe(['image'])
        ->and(builderBlockCollections($other))->toBe(['builder-block-' . PRUNE_DROP_KEY]);
});

it('keeps media when the saved content is not a block list', function (): void {
    $translation = translationWithBuilderMedia([['type' => 'logos', 'data' => ['media_key' => PRUNE_KEEP_KEY]]], PRUNE_KEEP_KEY);
    $translation->forceFill(['content' => '<p>html</p>'])->saveQuietly();

    expect(PruneUnreferencedBuilderBlockMediaAction::run($translation))->toBe(0)
        ->and(builderBlockCollections($translation))->toHaveCount(1);
});

it('bases its decision on persisted content, not on unsaved attribute changes', function (): void {
    $translation = translationWithBuilderMedia([['type' => 'logos', 'data' => ['media_key' => PRUNE_KEEP_KEY]]], PRUNE_KEEP_KEY);
    $translation->content = [['type' => 'content', 'data' => ['content' => '']]];

    expect(PruneUnreferencedBuilderBlockMediaAction::run($translation))->toBe(0)
        ->and(builderBlockCollections($translation))->toHaveCount(1);
});

it('prunes what a page editor save dropped when it saves its relationships, not while the form state changes', function (): void {
    $translation = translationWithBuilderMedia([['type' => 'logos', 'data' => ['media_key' => PRUNE_DROP_KEY]]], PRUNE_DROP_KEY);
    $schema = Schema::make(new SchemaStateHarness)->model($translation)->components([ContentBuilder::make('content')]);

    $translation->update(['content' => [['type' => 'content', 'data' => ['content' => '']]]]);

    expect(builderBlockCollections($translation))->toHaveCount(1);

    $schema->saveRelationships();

    expect(builderBlockCollections($translation))->toBe([]);
});

it('keeps media no saved content has referenced when a page editor saves, such as another editor\'s unsaved clone', function (): void {
    $translation = translationWithBuilderMedia([['type' => 'logos', 'data' => ['media_key' => PRUNE_KEEP_KEY]]], PRUNE_KEEP_KEY, PRUNE_DROP_KEY);
    $schema = Schema::make(new SchemaStateHarness)->model($translation)->components([ContentBuilder::make('content')]);

    $translation->update(['content' => [['type' => 'logos', 'data' => ['media_key' => PRUNE_KEEP_KEY, 'title' => 'Edited']]]]);
    $schema->saveRelationships();

    expect(builderBlockCollections($translation))->toBe(['builder-block-' . PRUNE_KEEP_KEY, 'builder-block-' . PRUNE_DROP_KEY]);
});

it('shares block media across a page\'s languages and keeps it while any translation still uses it', function (): void {
    $english = translationWithBuilderMedia([['type' => 'logos', 'data' => ['media_key' => PRUNE_KEEP_KEY]]], PRUNE_KEEP_KEY);
    $page = $english->mediaOwner();
    $french = Translation::factory()->translatable($page)->language(Language::factory()->french()->create())->create([
        'content' => [['type' => 'logos', 'data' => ['media_key' => PRUNE_KEEP_KEY]]],
    ]);
    $media = $page->getMedia('builder-block-' . PRUNE_KEEP_KEY)->sole();
    throw_unless($media instanceof Media, RuntimeException::class, 'Expected Capell media.');
    $media->translations()->create(['language_id' => $english->language_id, 'meta' => ['alt' => 'Partner logo']]);
    $media->translations()->create(['language_id' => $french->language_id, 'meta' => ['alt' => 'Logo du partenaire']]);

    $englishImages = ResolveBuilderBlockMediaAction::run($english, PRUNE_KEEP_KEY);
    $frenchImages = ResolveBuilderBlockMediaAction::run($french->refresh(), PRUNE_KEEP_KEY);

    expect(array_keys($frenchImages))->toBe([$media->uuid])->and(array_keys($englishImages))->toBe([$media->uuid])
        ->and($englishImages[$media->uuid]['alt'])->toBe('Partner logo')
        ->and($frenchImages[$media->uuid]['alt'])->toBe('Logo du partenaire');

    $english->update(['content' => [['type' => 'content', 'data' => ['content' => '']]]]);
    expect(PruneUnreferencedBuilderBlockMediaAction::run($english->refresh()))->toBe(0)
        ->and(builderBlockCollections($english))->toBe(['builder-block-' . PRUNE_KEEP_KEY]);

    $french->update(['content' => [['type' => 'content', 'data' => ['content' => '']]]]);
    expect(PruneUnreferencedBuilderBlockMediaAction::run($french->refresh()))->toBe(1)
        ->and(builderBlockCollections($english))->toBe([]);
});
