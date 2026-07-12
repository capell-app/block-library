<?php

declare(strict_types=1);

use Capell\BlockLibrary\Actions\ResolveBlockDefinitionAction;
use Capell\BlockLibrary\Enums\BlockAlignment;
use Capell\BlockLibrary\Enums\BlockColumnCount;
use Capell\BlockLibrary\Enums\BlockDividerStyle;
use Capell\BlockLibrary\Support\BuilderBlockDiscovery;
use Capell\BlockLibrary\Support\DefaultBlockCatalog;
use Capell\BlockLibrary\Support\DefaultBlockContentProvider;
use Filament\Forms\Components\Builder\Block;
use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;

it('registers every default catalog block with public views and screenshot contracts', function (): void {
    foreach (DefaultBlockCatalog::keys() as $key) {
        $definition = ResolveBlockDefinitionAction::run($key);

        expect($definition->sourcePackage)->toBe('capell-app/block-library')
            ->and($definition->publicViewName())->toBe(DefaultBlockCatalog::viewName($key))
            ->and($definition->screenshots)->toHaveCount(2);

        foreach ($definition->screenshots as $screenshot) {
            expect($screenshot->path)->toStartWith('docs/screenshots/block-library-')
                ->and($screenshot->path)->toEndWith('.png')
                ->and($screenshot->alt)->not->toBeEmpty()
                ->and($screenshot->caption)->not->toBeEmpty();
        }
    }
});

it('declares complete accessibility contracts for every default catalog block', function (): void {
    foreach (DefaultBlockCatalog::keys() as $key) {
        $definition = ResolveBlockDefinitionAction::run($key);
        $contract = $definition->accessibilityContract;

        expect($contract->semanticRules)->not->toBeEmpty($key . ' semantic rules')
            ->and($contract->keyboardRules)->not->toBeEmpty($key . ' keyboard rules')
            ->and($contract->contrastPairs)->not->toBeEmpty($key . ' contrast pairs')
            ->and($contract->mediaRules)->not->toBeEmpty($key . ' media rules');
    }
});

it('provides fixture and demo payloads for every default catalog block', function (): void {
    foreach (DefaultBlockCatalog::keys() as $key) {
        $definition = ResolveBlockDefinitionAction::run($key);

        expect($definition->fixtureProvider)->toBe(DefaultBlockContentProvider::class)
            ->and($definition->demoContentProvider)->toBe(DefaultBlockContentProvider::class);

        $provider = resolve(DefaultBlockContentProvider::class);
        expect($provider)->toBeInstanceOf(DefaultBlockContentProvider::class);

        $fixtures = iterator_to_array($provider->fixtures($definition));

        expect($fixtures)->toHaveCount(1)
            ->and($fixtures[0]->key)->toBe($key . '.default')
            ->and($fixtures[0]->payload)->toBe(DefaultBlockCatalog::fixturePayload($key))
            ->and($provider->demoContent($definition))->toBe(DefaultBlockCatalog::fixturePayload($key));
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

it('labels persisted builder block options from enums', function (): void {
    expect(BlockAlignment::Start->getLabel())->toBe(__('capell-block-library::blocks.fields.align_start'))
        ->and(BlockAlignment::Center->getLabel())->toBe(__('capell-block-library::blocks.fields.align_center'))
        ->and(BlockAlignment::End->getLabel())->toBe(__('capell-block-library::blocks.fields.align_end'))
        ->and(BlockColumnCount::Two->getLabel())->toBe(__('capell-block-library::blocks.fields.columns_2'))
        ->and(BlockColumnCount::Three->getLabel())->toBe(__('capell-block-library::blocks.fields.columns_3'))
        ->and(BlockColumnCount::Four->getLabel())->toBe(__('capell-block-library::blocks.fields.columns_4'))
        ->and(BlockDividerStyle::Line->getLabel())->toBe(__('capell-block-library::blocks.fields.style_line'))
        ->and(BlockDividerStyle::Dots->getLabel())->toBe(__('capell-block-library::blocks.fields.style_dots'));
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
        /** @var view-string $viewName */
        $viewName = DefaultBlockCatalog::viewName($key);

        $html = view($viewName, DefaultBlockCatalog::fixturePayload($key))->render();

        TestResponse::fromBaseResponse(new Response('<!DOCTYPE html><html><body>' . $html . '</body></html>'))
            ->assertContainsElement('body .section');
    }
});

it('sanitizes hostile rich text inside block catalog views', function (): void {
    $html = view('capell-block-library::blocks.catalog.accordion', [
        'asset' => null,
        'title' => 'Safe title',
        'summary' => '<script>alert(1)</script><p onclick="alert(2)">Safe summary</p>',
        'meta' => [
            'items' => [[
                'heading' => 'Safe heading',
                'content' => '<iframe src="https://attacker.test"></iframe><a href="javascript:alert(3)">Safe link</a>',
            ]],
        ],
    ])->render();

    expect($html)->toContain('Safe summary', 'Safe link')
        ->not->toContain('<script', '<iframe', 'onclick=', 'javascript:');
});

it('keeps every default public block view free of authoring and package internals', function (): void {
    $forbiddenNeedles = [
        'capell-app/block-library',
        'contenteditable',
        'data-capell',
        'field_path',
        'filament',
        'model_id',
        'package-health',
        'signed',
        'wire:',
        'x-filament',
    ];

    $forbiddenBladeSourceNeedles = [
        '::query(',
        'DB::',
        'loadMissing(',
        '->load(',
    ];

    foreach (DefaultBlockCatalog::keys() as $key) {
        /** @var view-string $viewName */
        $viewName = DefaultBlockCatalog::viewName($key);
        $payload = DefaultBlockCatalog::fixturePayload($key);
        $html = view($viewName, $payload)->render();

        $response = TestResponse::fromBaseResponse(new Response('<!DOCTYPE html><html><body>' . $html . '</body></html>'));

        foreach ($forbiddenNeedles as $needle) {
            $response->assertDontSee($needle, false);
        }

        $source = file_get_contents(__DIR__ . '/../../resources/views/blocks/catalog/' . DefaultBlockCatalog::viewSlug($key) . '.blade.php');

        expect($source)->toBeString();

        foreach ($forbiddenBladeSourceNeedles as $needle) {
            expect($source)->not->toContain($needle);
        }
    }
});
