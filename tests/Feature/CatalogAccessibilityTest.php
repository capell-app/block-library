<?php

declare(strict_types=1);

use Capell\BlockLibrary\Actions\BuildBuilderBlockRenderDataAction;
use Capell\BlockLibrary\View\Components\BuilderBlock;
use Illuminate\View\ComponentAttributeBag;

/**
 * @param  view-string  $view
 * @param  array<string, mixed>  $meta
 */
function renderAccessibleCatalogView(string $view, array $meta): string
{
    return view($view, [
        'asset' => null,
        'meta' => $meta,
        'summary' => null,
        'title' => null,
        'attributes' => new ComponentAttributeBag,
    ])->render();
}

/**
 * @return list<string>
 */
function catalogIds(string $html): array
{
    preg_match_all('/\sid="([^"]+)"/', $html, $matches);

    return $matches[1];
}

it('gives two accordions on one page distinct panel ids that their buttons control', function (): void {
    $first = renderAccessibleCatalogView('capell-block-library::blocks.catalog.accordion', ['items' => [
        ['heading' => 'One', 'content' => '<p>First answer</p>'],
    ]]);
    $second = renderAccessibleCatalogView('capell-block-library::blocks.catalog.accordion', ['items' => [
        ['heading' => 'Two', 'content' => '<p>Second answer</p>'],
    ]]);

    expect(array_intersect(catalogIds($first), catalogIds($second)))->toBe([])
        ->and(catalogIds($first))->toHaveCount(1);

    expect(preg_match('/aria-controls="([^"]+)"/', $first, $controls))->toBe(1)
        ->and($controls[1] ?? null)->toBe(catalogIds($first)[0]);
});

it('gives two faq blocks distinct panel ids', function (): void {
    $first = renderAccessibleCatalogView('capell-block-library::blocks.catalog.faq', ['questions' => [
        ['question' => 'Why?', 'answer' => '<p>Because.</p>'],
    ]]);
    $second = renderAccessibleCatalogView('capell-block-library::blocks.catalog.faq', ['questions' => [
        ['question' => 'How?', 'answer' => '<p>Like this.</p>'],
    ]]);

    expect(array_intersect(catalogIds($first), catalogIds($second)))->toBe([])
        ->and(catalogIds($first))->toHaveCount(1);
});

it('does not point aria-controls at a panel that is not rendered', function (): void {
    $accordion = renderAccessibleCatalogView('capell-block-library::blocks.catalog.accordion', ['items' => [
        ['heading' => 'Empty', 'content' => ''],
    ]]);
    $faq = renderAccessibleCatalogView('capell-block-library::blocks.catalog.faq', ['questions' => [
        ['question' => 'Empty?', 'answer' => ''],
    ]]);

    expect($accordion)->not->toContain('aria-controls')
        ->and($faq)->not->toContain('aria-controls');
});

it('renders a logo without a link as a non-link and never as a dead anchor', function (): void {
    $html = renderAccessibleCatalogView('capell-block-library::blocks.catalog.logos', ['logos' => [
        ['name' => 'Linked', 'url' => 'https://linked.example'],
        ['name' => 'Unlinked', 'url' => null, 'image' => ['url' => '/storage/1/u.png', 'alt' => '']],
    ]]);

    expect($html)
        ->not->toContain('href="#"')
        ->toContain('href="https://linked.example"')
        ->and(preg_match_all('/<a\b/', $html))->toBe(1)
        ->and($html)->toContain('alt="Unlinked"');
});

it('skips call to action buttons without a destination', function (): void {
    $html = renderAccessibleCatalogView('capell-block-library::blocks.catalog.call-to-action', ['actions' => [
        ['label' => 'Dead', 'url' => null],
        ['label' => 'Live', 'url' => 'https://live.example'],
    ]]);

    expect($html)
        ->not->toContain('href="#"')
        ->not->toContain('Dead')
        ->toContain('Live');
});

it('reserves image space with width and height when the media dimensions are known', function (): void {
    $logos = renderAccessibleCatalogView('capell-block-library::blocks.catalog.logos', ['logos' => [
        ['name' => 'Acme', 'url' => 'https://acme.example', 'image' => ['url' => '/storage/1/a.png', 'alt' => 'Acme', 'width' => 320, 'height' => 120]],
    ]]);
    $team = renderAccessibleCatalogView('capell-block-library::blocks.catalog.team', ['columns' => '3', 'members' => [
        ['name' => 'Ada', 'image' => ['url' => '/storage/2/ada.png', 'alt' => 'Ada', 'width' => 200, 'height' => 200]],
    ]]);

    expect($logos)->toContain('width="320"')->toContain('height="120"')
        ->and($team)->toContain('width="200"')->toContain('height="200"');
});

it('gives identical accordion blocks on one request distinct panel ids', function (): void {
    $state = ['items' => [['heading' => 'Same', 'content' => '<p>Same answer</p>']]];
    $render = fn (): string => new BuilderBlock('accordion', BuildBuilderBlockRenderDataAction::run('accordion', $state))->render()->render();

    $first = $render();
    $second = $render();

    expect(catalogIds($first))->toHaveCount(1)
        ->and(array_intersect(catalogIds($first), catalogIds($second)))->toBe([]);
});
