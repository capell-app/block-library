<?php

declare(strict_types=1);

use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Support\BlockRegistryManifestCompiler;
use Capell\ContentBlocks\Support\BlockRegistryManifestStore;
use Illuminate\Filesystem\Filesystem;

it('compiles structural registry metadata without localized labels', function (): void {
    $compiler = new BlockRegistryManifestCompiler;
    $manifest = $compiler->compile([
        'marketing.hero' => new BlockDefinitionData(
            key: 'marketing.hero',
            label: 'Marketing hero',
            description: 'A campaign-ready hero block.',
            category: 'marketing',
            view: 'vendor-package::blocks.marketing-hero',
            sourcePackage: 'vendor/package',
        ),
    ]);

    expect($manifest['blocks']['marketing.hero'])
        ->toMatchArray([
            'key' => 'marketing.hero',
            'category' => 'marketing',
            'sourcePackage' => 'vendor/package',
            'publicView' => 'vendor-package::blocks.marketing-hero',
            'previewView' => 'vendor-package::blocks.marketing-hero',
            'defaultVariant' => 'default',
            'variants' => ['default'],
        ])
        ->and($manifest['blocks']['marketing.hero'])->not->toHaveKeys(['label', 'description']);
});

it('filters stale manifest entries for inactive packages and missing views', function (): void {
    $compiler = new BlockRegistryManifestCompiler(
        packageIsActive: static fn (string $package): bool => $package === 'active/package',
        viewExists: static fn (string $view): bool => in_array($view, ['active-package::blocks.hero', 'active-package::admin.hero'], true),
    );

    $validBlocks = $compiler->validBlocks([
        'blocks' => [
            'active.hero' => [
                'key' => 'active.hero',
                'sourcePackage' => 'active/package',
                'publicView' => 'active-package::blocks.hero',
                'previewView' => 'active-package::blocks.hero',
                'fixtureProvider' => null,
                'demoContentProvider' => null,
            ],
            'inactive.hero' => [
                'key' => 'inactive.hero',
                'sourcePackage' => 'inactive/package',
                'publicView' => 'inactive-package::blocks.hero',
                'previewView' => 'inactive-package::blocks.hero',
                'fixtureProvider' => null,
                'demoContentProvider' => null,
            ],
            'missing-view.hero' => [
                'key' => 'missing-view.hero',
                'sourcePackage' => 'active/package',
                'publicView' => 'active-package::blocks.missing',
                'previewView' => 'active-package::blocks.missing',
                'fixtureProvider' => null,
                'demoContentProvider' => null,
            ],
            'invalid-provider.hero' => [
                'key' => 'invalid-provider.hero',
                'sourcePackage' => 'active/package',
                'publicView' => 'active-package::blocks.hero',
                'previewView' => 'active-package::blocks.hero',
                'fixtureProvider' => stdClass::class,
                'demoContentProvider' => null,
            ],
            'admin-view.hero' => [
                'key' => 'admin-view.hero',
                'sourcePackage' => 'active/package',
                'publicView' => 'active-package::admin.hero',
                'previewView' => 'active-package::blocks.hero',
                'fixtureProvider' => null,
                'demoContentProvider' => null,
            ],
        ],
    ]);

    expect($validBlocks)->toHaveKey('active.hero')
        ->and($validBlocks)->not->toHaveKeys(['inactive.hero', 'missing-view.hero', 'invalid-provider.hero', 'admin-view.hero']);
});

it('writes registry manifests atomically and removes temporary files', function (): void {
    $filesystem = new Filesystem;
    $path = sys_get_temp_dir() . '/capell-content-blocks-manifest.php';
    $store = new BlockRegistryManifestStore($filesystem, $path);

    $store->forget();
    $store->replaceAtomically([
        'blocks' => ['marketing.hero' => ['key' => 'marketing.hero']],
    ]);

    $temporaryFiles = $filesystem->glob($path . '.*.tmp');

    expect($store->read()['blocks'])->toHaveKey('marketing.hero')
        ->and($temporaryFiles === false ? [] : $temporaryFiles)->toBe([]);

    $store->forget();
});

it('fails manifest writes when the lock cannot be acquired', function (): void {
    $filesystem = new class extends Filesystem
    {
        public function put($path, $contents, $lock = false): bool|int
        {
            throw new RuntimeException('Manifest should not be written without a lock.');
        }
    };
    $path = sys_get_temp_dir() . '/capell-content-blocks-lock-failure-manifest.php';
    $store = new BlockRegistryManifestStore(
        filesystem: $filesystem,
        path: $path,
        lockAcquirer: static fn (mixed $lockHandle): bool => false,
    );

    try {
        $store->replaceAtomically([
            'blocks' => ['marketing.hero' => ['key' => 'marketing.hero']],
        ]);
    } finally {
        $store->forget();
        $filesystem->delete($path . '.lock');
    }
})->throws(RuntimeException::class, 'Unable to lock content block manifest');

it('returns null for missing or corrupt manifests so callers can use safe cold-start fallback', function (): void {
    $filesystem = new Filesystem;
    $path = sys_get_temp_dir() . '/capell-content-blocks-corrupt-manifest.php';
    $store = new BlockRegistryManifestStore($filesystem, $path);

    $store->forget();

    expect($store->read())->toBeNull();

    $filesystem->put($path, '<?php throw new RuntimeException("corrupt");');

    expect($store->read())->toBeNull();

    $store->forget();
});
