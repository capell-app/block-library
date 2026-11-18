<?php

declare(strict_types=1);

use Capell\BlockLibrary\Health\BlockLibraryHealthCheck;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;

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
