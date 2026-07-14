<?php

declare(strict_types=1);

use Capell\BlockLibrary\Providers\BlockLibraryServiceProvider;
use Illuminate\Support\Facades\File;

describe('block-library capell.json manifest', function (): void {
    it('supports the Filament version required by the Capell platform', function (): void {
        /** @var array{require: array<string, string>} $composer */
        $composer = json_decode(
            File::get(__DIR__ . '/../../composer.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        expect($composer['require']['filament/forms'] ?? null)->toBe('~5.6.8');
    });

    it('declares the foundation package metadata and provider', function (): void {
        $manifest = json_decode(
            File::get(__DIR__ . '/../../capell.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        expect($manifest)
            ->toMatchArray([
                'name' => 'capell-app/block-library',
                'slug' => 'block-library',
                'kind' => 'package',
                'capellApiVersion' => '^1.0',
                'product' => [
                    'group' => 'Capell Foundation',
                    'tier' => 'free',
                    'bundle' => 'foundation',
                ],
            ])
            ->and($manifest['surfaces'])->toContain('shared')
            ->and($manifest['providers']['runtime'])->toContain(BlockLibraryServiceProvider::class);
    });

    it('documents the custom block integration contract for package authors', function (): void {
        $packagePath = dirname(__DIR__, 2);
        $docsIndex = File::get($packagePath . '/docs/README.md');
        $readme = File::get($packagePath . '/README.md');
        $guide = File::get($packagePath . '/docs/custom-blocks.md');

        expect($docsIndex)->toContain('custom-blocks.md')
            ->and($readme)->toContain('Reusable foundation content blocks')
            ->and($guide)->toContain(
                'BlockDefinitionProvider',
                'BlockDefinitionData',
                'BlockFixtureProvider',
                'FilamentBuilderBlock',
                'docs/screenshots.json',
                'BuilderBlockDiscovery',
            );
    });
});
