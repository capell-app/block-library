<?php

declare(strict_types=1);

use Capell\BlockLibrary\Providers\BlockLibraryServiceProvider;
use Illuminate\Support\Facades\File;

describe('block-library capell.json manifest', function (): void {
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
                'capellApiVersion' => '^4.0',
                'product' => [
                    'group' => 'Capell Foundation',
                    'tier' => 'free',
                    'bundle' => 'foundation',
                ],
            ])
            ->and($manifest['surfaces'])->toContain('shared')
            ->and($manifest['providers']['runtime'])->toContain(BlockLibraryServiceProvider::class);
    });
});
