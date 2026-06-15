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
        /** @var view-string $viewName */
        $viewName = DefaultBlockCatalog::viewName($key);

        $html = view($viewName, [
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
        $payload = blockLibraryCatalogRenderPayload($key);
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

/**
 * @return array{asset: null, title: string, summary: string, meta: array<string, mixed>, url?: string}
 */
function blockLibraryCatalogRenderPayload(string $key): array
{
    return [
        'asset' => null,
        'title' => 'Preview',
        'summary' => '<p>Reusable public output.</p>',
        'url' => '#',
        'meta' => match ($key) {
            'accordion' => [
                'first_open' => true,
                'items' => [
                    ['heading' => 'Question one', 'content' => '<p>Answer one.</p>'],
                ],
            ],
            'call_to_action' => [
                'alignment' => 'center',
                'actions' => [
                    ['label' => 'Start', 'url' => '#'],
                ],
            ],
            'comparison' => [
                'columns' => [
                    ['heading' => 'Standard', 'description' => 'Default plan.'],
                    ['heading' => 'Pro', 'description' => 'Advanced plan.', 'highlighted' => true],
                ],
                'rows' => [
                    ['label' => 'Blocks', 'values' => '17|17'],
                ],
            ],
            'counter' => [
                'counters' => [
                    ['value' => '17', 'suffix' => '+', 'label' => 'Blocks', 'description' => 'Default catalog blocks.'],
                ],
            ],
            'divider' => [
                'style' => 'dots',
            ],
            'faq' => [
                'first_open' => true,
                'questions' => [
                    ['question' => 'Is it public-safe?', 'answer' => '<p>Yes.</p>'],
                ],
            ],
            'features' => [
                'columns' => '3',
                'features' => [
                    ['heading' => 'Typed definitions', 'description' => 'Blocks are registered with typed data.', 'url' => '#'],
                ],
            ],
            'hero' => [
                'alignment' => 'center',
            ],
            'logos' => [
                'columns' => '4',
                'logos' => [
                    ['name' => 'Capell', 'url' => '#'],
                ],
            ],
            'pricing' => [
                'plans' => [
                    [
                        'name' => 'Foundation',
                        'price' => 'Free',
                        'period' => 'site',
                        'description' => 'Included with Capell.',
                        'features' => "Blocks\nBuilder integration",
                        'highlighted' => true,
                        'action_label' => 'Use block',
                        'action_url' => '#',
                    ],
                ],
            ],
            'stats' => [
                'columns' => '3',
                'stats' => [
                    ['value' => '17', 'label' => 'Blocks', 'description' => 'Catalog size.'],
                ],
            ],
            'table' => [
                'caption' => 'Catalog coverage',
                'headers' => [
                    ['label' => 'Block'],
                    ['label' => 'Safe'],
                ],
                'rows' => [
                    ['cells' => 'Hero|Yes'],
                ],
            ],
            'tabs' => [
                'tabs' => [
                    ['label' => 'Plan', 'content' => '<p>Build with approved blocks.</p>'],
                ],
            ],
            'team' => [
                'columns' => '3',
                'members' => [
                    ['name' => 'Editor', 'role' => 'Publisher', 'bio' => 'Builds pages.', 'url' => '#'],
                ],
            ],
            'testimonial' => [
                'quote' => 'Reusable blocks keep public output predictable.',
                'author' => 'Capell',
                'role' => 'Package developer',
            ],
            'timeline' => [
                'milestones' => [
                    ['date' => '2026', 'heading' => 'Catalog', 'description' => 'Default blocks ship.'],
                ],
            ],
            default => [],
        },
    ];
}
