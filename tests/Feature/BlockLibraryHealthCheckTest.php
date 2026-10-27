<?php

declare(strict_types=1);

use Capell\BlockLibrary\Data\BlockDefinitionData;
use Capell\BlockLibrary\Health\BlockLibraryHealthCheck;
use Capell\BlockLibrary\Support\BlockRegistry;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Tests\Support\ScreenshotManifest;
use Illuminate\Support\Facades\Lang;

it('returns a compatible capell api version', function (): void {
    expect(BlockLibraryHealthCheck::compatibleCapellApiVersion())->toBe('^1.0');
});

it('runs actionable catalog diagnostics', function (): void {
    $results = BlockLibraryHealthCheck::runDiagnostics();

    expect($results)->toHaveCount(10)
        ->and($results->every(static fn (mixed $check): bool => $check instanceof DoctorCheckResultData))->toBeTrue()
        ->and($results->pluck('id')->all())->toBe([
            'legacy.block.library.registry.binding',
            'legacy.block.library.default.catalog.definitions',
            'legacy.block.library.accessibility.contracts',
            'legacy.block.library.schema.lifecycle.metadata',
            'legacy.block.library.fixture.and.demo.providers',
            'legacy.block.library.catalog.translations',
            'legacy.block.library.catalog.views',
            'legacy.block.library.filament.builder.blocks',
            'legacy.block.library.catalog.screenshots',
            'legacy.block.library.manifest.health.declaration',
        ]);
});

it('passes health diagnostics for the shipped default catalog', function (): void {
    $results = BlockLibraryHealthCheck::runDiagnostics();

    expect(BlockLibraryHealthCheck::passed())->toBeTrue();

    foreach ($results as $result) {
        expect($result->passed)->toBeTrue($result->label . ': ' . $result->message);
    }
});

it('renders the screenshot registry route from registered block definitions', function (): void {
    require dirname(__DIR__, 2) . '/workbench/routes/screenshot-fixtures.php';

    $response = $this->get('/screenshot-fixtures/catalogue/block-library/content-block-registry-list');

    $response->assertOk()->assertSee('Content block registry')->assertSee('Hero')->assertDontSee('Illustrative');
    expect(resolve(BlockRegistry::class)->all())->not->toBeEmpty();

    $entry = ScreenshotManifest::entry(dirname(__DIR__, 2) . '/docs/screenshots.json', 'content-block-registry-list');
    $selector = $entry['waitFor'] ?? null;
    if (! is_string($selector) || trim($selector) === '') {
        throw new LogicException('The registry capture requires a readiness selector.');
    }

    $response->assertElementExists($selector);
});

it('translates registered block labels and descriptions while preserving literal definitions', function (): void {
    require dirname(__DIR__, 2) . '/workbench/routes/screenshot-fixtures.php';

    $definitions = resolve(BlockRegistry::class)->all();
    $definition = reset($definitions);
    if (! $definition instanceof BlockDefinitionData) {
        throw new LogicException('Expected a registered block definition.');
    }

    Lang::addLines([
        'capture.registry.label' => 'Translated registry block',
        'capture.registry.description' => 'Translated registry description.',
    ], 'en');
    $definition->label = 'capture.registry.label';
    $definition->description = 'capture.registry.description';

    $this->get('/screenshot-fixtures/catalogue/block-library/content-block-registry-list')
        ->assertOk()
        ->assertSee('Translated registry block')
        ->assertSee('Translated registry description.')
        ->assertSee('Hero')
        ->assertDontSee('capture.registry.label')
        ->assertDontSee('capture.registry.description');
});
