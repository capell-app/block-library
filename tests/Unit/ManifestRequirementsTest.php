<?php

declare(strict_types=1);

use Capell\BlockLibrary\Providers\BlockLibraryServiceProvider;
use Composer\Semver\Intervals;
use Composer\Semver\VersionParser;
use Illuminate\Support\Facades\File;

describe('block-library capell.json manifest', function (): void {
    it('supports the Filament version required by the Capell platform', function (): void {
        /** @var array{require: array<string, string>} $composer */
        $composer = json_decode(
            File::get(__DIR__ . '/../../composer.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        /** @var array{require: array<string, string>} $platformComposer */
        $platformComposer = json_decode(
            File::get(dirname(__DIR__, 4) . '/composer.json'),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
        $formsConstraint = $composer['require']['filament/forms'] ?? null;
        expect($formsConstraint)->toBeString()->not->toBeEmpty();
        throw_unless(is_string($formsConstraint), RuntimeException::class, 'Block Library must declare a Filament Forms constraint.');
        $versionParser = new VersionParser;

        // Cover the whole platform range even when this package also supports another line.
        expect(Intervals::isSubsetOf(
            $versionParser->parseConstraints($platformComposer['require']['filament/filament']),
            $versionParser->parseConstraints($formsConstraint),
        ))->toBeTrue();
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
            ->and($readme)->toContain('Block Library supplies typed, reusable content-block definitions and matching Filament Builder blocks for Capell content packages.')
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
