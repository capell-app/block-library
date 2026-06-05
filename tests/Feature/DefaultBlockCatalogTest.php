<?php

declare(strict_types=1);

use Capell\BlockLibrary\Actions\ResolveBlockDefinitionAction;
use Capell\BlockLibrary\Support\BuilderBlockDiscovery;
use Capell\BlockLibrary\Support\DefaultBlockCatalog;
use Filament\Forms\Components\Builder\Block;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use Sinnbeck\DomAssertions\Asserts\AssertElement;

it('registers every default catalog block with public views and screenshots', function (): void {
    foreach (DefaultBlockCatalog::keys() as $key) {
        $definition = ResolveBlockDefinitionAction::run($key);

        expect($definition->sourcePackage)->toBe('capell-app/block-library')
            ->and($definition->publicViewName())->toBe(DefaultBlockCatalog::viewName($key))
            ->and($definition->screenshots)->toHaveCount(2);

        foreach ($definition->screenshots as $screenshot) {
            expect(file_exists(__DIR__ . '/../../' . $screenshot->path))->toBeTrue($screenshot->path);
        }
    }
});

it('discovers the default filament builder blocks', function (): void {
    $blocks = resolve(BuilderBlockDiscovery::class)->filamentBlocks();
    $names = array_map(
        static fn (Block $block): string => $block->getName(),
        $blocks,
    );

    expect($names)->toContain(...DefaultBlockCatalog::keys());
});

it('renders interactive public blocks without authoring surface', function (): void {
    $html = view('capell-block-library::blocks.catalog.accordion', [
        'asset' => null,
        'title' => 'Questions',
        'meta' => ['first_open' => true, 'items' => [['heading' => 'What is included?', 'content' => '<p>Reusable public output.</p>']]],
    ])->render();

    $html .= view('capell-block-library::blocks.catalog.tabs', [
        'asset' => null,
        'title' => 'Workflow',
        'meta' => ['tabs' => [['label' => 'Plan', 'content' => '<p>Plan the page.</p>']]],
    ])->render();

    TestResponse::fromBaseResponse(new Response('<!DOCTYPE html><html><body>' . $html . '</body></html>'))
        ->assertContainsElement('section.section-accordion[x-data]')
        ->assertContainsElement('section.section-tabs [role="tablist"]')
        ->assertDontSee('filament')
        ->assertDontSee('signed')
        ->assertDontSee('wire:')
        ->assertDontSee('contenteditable');
});

it('renders every default public block view', function (): void {
    foreach (DefaultBlockCatalog::keys() as $key) {
        $html = view(DefaultBlockCatalog::viewName($key), [
            'asset' => null,
            'title' => 'Preview',
            'summary' => '<p>Summary</p>',
            'meta' => [],
            'url' => '#',
        ])->render();

        TestResponse::fromBaseResponse(new Response('<!DOCTYPE html><html><body>' . $html . '</body></html>'))
            ->assertElementExists('body', static function (AssertElement $body): void {
                $body->contains('.section', 1);
            });
    }
});
