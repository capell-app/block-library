<?php

declare(strict_types=1);

use Illuminate\View\ComponentAttributeBag;

/**
 * @param  view-string  $view
 * @param  array<string, mixed>  $meta
 */
function renderCatalogView(string $view, array $meta): string
{
    return view($view, [
        'asset' => null,
        'meta' => $meta,
        'summary' => null,
        'title' => null,
        'attributes' => new ComponentAttributeBag,
    ])->render();
}

it('renders a hydrated logo image with the media alt text and keeps the name as the fallback alt', function (): void {
    $html = renderCatalogView('capell-block-library::blocks.catalog.logos', ['columns' => '4', 'logos' => [
        ['name' => 'Acme', 'url' => 'https://acme.example', 'image' => ['url' => '/storage/1/acme.png', 'alt' => 'Acme wordmark']],
        ['name' => 'Globex', 'url' => 'https://globex.example', 'image' => ['url' => '/storage/2/globex.png', 'alt' => null]],
        ['name' => 'Hooli', 'url' => 'https://hooli.example', 'image' => ['url' => '/storage/3/hooli.png', 'alt' => '']],
        ['name' => 'Initech', 'url' => 'https://initech.example'],
    ]]);

    expect($html)
        ->toContain('src="/storage/1/acme.png"')
        ->toContain('alt="Acme wordmark"')
        ->toContain('src="/storage/2/globex.png"')
        ->toContain('alt="Globex"')
        ->toContain('src="/storage/3/hooli.png"')
        ->toContain('alt=""')
        ->toContain('Initech')
        ->and(substr_count($html, '<img'))->toBe(3)
        ->and(preg_match_all('/<a\b/', $html))->toBe(4)
        ->and(substr_count($html, 'aria-label='))->toBe(1);
});

it('keeps an accessible name on a logo link whose image is decorative', function (): void {
    $html = renderCatalogView('capell-block-library::blocks.catalog.logos', ['logos' => [
        ['name' => 'Hooli', 'url' => 'https://hooli.example', 'image' => ['url' => '/storage/3/hooli.png', 'alt' => '']],
        ['name' => 'Acme', 'url' => 'https://acme.example', 'image' => ['url' => '/storage/1/acme.png', 'alt' => 'Acme wordmark']],
    ]]);
    $document = new DOMDocument;
    @$document->loadHTML('<html><body>' . $html . '</body></html>');
    $anchors = iterator_to_array($document->getElementsByTagName('a'), false);

    expect($anchors)->toHaveCount(2)
        ->and($anchors[0]->getAttribute('aria-label'))->toBe('Hooli')
        ->and(anchorImageAlt($anchors[0]))->toBe('')
        ->and($anchors[1]->hasAttribute('aria-label'))->toBeFalse()
        ->and(anchorImageAlt($anchors[1]))->toBe('Acme wordmark');
});

function anchorImageAlt(DOMElement $anchor): ?string
{
    $image = $anchor->getElementsByTagName('img')->item(0);

    return $image instanceof DOMElement ? $image->getAttribute('alt') : null;
}

it('escapes hydrated image values in logo and team output', function (): void {
    $logos = renderCatalogView('capell-block-library::blocks.catalog.logos', ['logos' => [
        ['name' => 'Acme', 'image' => ['url' => '/storage/a.png" onerror="x()', 'alt' => '"><script>x()</script>']],
    ]]);
    $team = renderCatalogView('capell-block-library::blocks.catalog.team', ['columns' => '3', 'members' => [
        ['name' => 'Ada', 'image' => ['url' => '/storage/ada.png', 'alt' => '"><script>x()</script>']],
    ]]);

    expect($logos)->not->toContain('<script>')->not->toContain('" onerror="')
        ->and($team)->not->toContain('<script>');
});

it('renders a team member photo with the media alt text, or an empty alt when the heading already names them', function (): void {
    $html = renderCatalogView('capell-block-library::blocks.catalog.team', ['columns' => '3', 'members' => [
        ['name' => 'Ada Lovelace', 'image' => ['url' => '/storage/ada.png', 'alt' => 'Ada at her desk']],
        ['name' => 'Grace Hopper', 'image' => ['url' => '/storage/grace.png', 'alt' => null]],
    ]]);

    expect($html)->toContain('alt="Ada at her desk"')->toContain('alt=""')->not->toContain('alt="Grace Hopper"');
});

it('renders a team member photo or falls back to the initial', function (): void {
    $html = renderCatalogView('capell-block-library::blocks.catalog.team', ['columns' => '3', 'members' => [
        ['name' => 'Ada Lovelace', 'image' => ['url' => '/storage/ada.png', 'alt' => null]],
        ['name' => 'Grace Hopper'],
    ]]);

    expect($html)
        ->toContain('src="/storage/ada.png"')
        ->and(substr_count($html, '<img'))->toBe(1)
        ->and($html)->toMatch('/>\s*G\s*</');
});

it('ignores an image entry without a url', function (): void {
    $html = renderCatalogView('capell-block-library::blocks.catalog.logos', ['logos' => [['name' => 'Acme', 'image' => ['url' => '', 'alt' => 'x']]]]);

    expect($html)->not->toContain('<img')->toContain('Acme');
});
