<?php

declare(strict_types=1);

use Capell\BlockLibrary\Contracts\FilamentBuilderBlock;
use Capell\BlockLibrary\Enums\BuilderBlockTarget;
use Capell\BlockLibrary\Support\BlockRegistry;
use Capell\BlockLibrary\Support\BuilderBlockDiscovery;
use Capell\BlockLibrary\Support\BuilderBlockRegistry;
use Capell\BlockLibrary\Support\DefaultBlockCatalog;
use Capell\BlockLibrary\Tests\Fixtures\BuilderBlocks\HeroBuilderBlock;
use Capell\BlockLibrary\Tests\Fixtures\BuilderBlocks\LegacyBuilderBlock;
use Filament\Forms\Components\Builder\Block;
use Illuminate\Filesystem\Filesystem;

function forceBuilderBlockDiscoveryCacheHit(BuilderBlockDiscovery $discovery): void
{
    $hasCachedBlocks = new ReflectionProperty($discovery, 'hasCachedBlocks');
    $hasCachedBlocks->setValue($discovery, true);
}

function temporaryBuilderBlockCachePath(string $suffix): string
{
    return sys_get_temp_dir() . '/capell-block-library-builder-blocks-' . $suffix . '.php';
}

function temporaryBuilderBlockDirectory(string $suffix): string
{
    return sys_get_temp_dir() . '/capell-block-library-builder-blocks-' . $suffix;
}

it('binds the builder block registry and discovery separately from typed content blocks', function (): void {
    expect(resolve(BuilderBlockRegistry::class))->toBeInstanceOf(BuilderBlockRegistry::class)
        ->and(resolve(BuilderBlockDiscovery::class))->toBeInstanceOf(BuilderBlockDiscovery::class)
        ->and(resolve(BlockRegistry::class))->toBeInstanceOf(BlockRegistry::class);
});

it('registers explicit builder block classes and exposes filament block instances', function (): void {
    $registry = new BuilderBlockRegistry;
    $discovery = new BuilderBlockDiscovery($registry, new Filesystem);

    $discovery->register(HeroBuilderBlock::class);

    $blocks = $discovery->filamentBlocks();

    expect($registry->get('hero', BuilderBlockTarget::AdminFilament))->toBe(HeroBuilderBlock::class)
        ->and($blocks)->toHaveCount(1)
        ->and($blocks[0])->toBeInstanceOf(Block::class)
        ->and($blocks[0]->getName())->toBe('hero');
});

it('registers legacy builder block classes by their old static contract', function (): void {
    $registry = new BuilderBlockRegistry;
    $discovery = new BuilderBlockDiscovery($registry, new Filesystem);

    $discovery->register(LegacyBuilderBlock::class);

    $blocks = $discovery->filamentBlocks();

    expect($registry->get('legacy', BuilderBlockTarget::AdminFilament))->toBe(LegacyBuilderBlock::class)
        ->and($blocks)->toHaveCount(1)
        ->and($blocks[0])->toBeInstanceOf(Block::class)
        ->and($blocks[0]->getName())->toBe('legacy');
});

it('discovers concrete filament builder block implementations from registered paths', function (): void {
    $registry = new BuilderBlockRegistry;
    $discovery = new BuilderBlockDiscovery($registry, new Filesystem);

    $discovery->registerDiscoverableBlocks(
        __DIR__ . '/../Fixtures/BuilderBlocks',
        'Capell\\BlockLibrary\\Tests\\Fixtures\\BuilderBlocks',
    );

    expect($discovery->filamentBlocks())->toHaveCount(2)
        ->and($registry->allForTarget(BuilderBlockTarget::AdminFilament))->toBe([
            'hero' => HeroBuilderBlock::class,
            'legacy' => LegacyBuilderBlock::class,
        ]);
});

it('adds catalog icons to discovered default Filament builder blocks', function (): void {
    $blocksByName = collect(resolve(BuilderBlockDiscovery::class)->filamentBlocks())
        ->keyBy(fn (Block $block): string => $block->getName());

    foreach (DefaultBlockCatalog::keys() as $key) {
        expect($blocksByName->has($key))->toBeTrue()
            ->and($blocksByName->get($key)?->getIcon())->toBe(DefaultBlockCatalog::icon($key));
    }
});

it('caches discovered builder block classes for warm starts', function (): void {
    $filesystem = new Filesystem;
    $cachePath = temporaryBuilderBlockCachePath('warm-start');
    $registry = new BuilderBlockRegistry;
    $discovery = new BuilderBlockDiscovery($registry, $filesystem, $cachePath);

    $filesystem->delete($cachePath);

    $discovery->registerDiscoverableBlocks(
        __DIR__ . '/../Fixtures/BuilderBlocks',
        'Capell\\BlockLibrary\\Tests\\Fixtures\\BuilderBlocks',
    );

    $discovery->cacheBlocks();

    try {
        $cached = require $cachePath;

        expect($cached)->toMatchArray([
            'version' => 2,
            'blocks' => [
                'hero' => HeroBuilderBlock::class,
                'legacy' => LegacyBuilderBlock::class,
            ],
        ])
            ->and($cached['signature'])->toBeString()->not->toBe('');
    } finally {
        $filesystem->delete($cachePath);
    }
});

it('restores cached builder block classes when discovery sources are unchanged', function (): void {
    $filesystem = new Filesystem;
    $cachePath = temporaryBuilderBlockCachePath('restore');
    $sourceDirectory = __DIR__ . '/../Fixtures/BuilderBlocks';

    $filesystem->delete($cachePath);

    $writer = new BuilderBlockDiscovery(new BuilderBlockRegistry, $filesystem, $cachePath);
    $writer->registerDiscoverableBlocks(
        $sourceDirectory,
        'Capell\\BlockLibrary\\Tests\\Fixtures\\BuilderBlocks',
    );
    $writer->cacheBlocks();

    $registry = new BuilderBlockRegistry;
    $reader = new BuilderBlockDiscovery($registry, $filesystem, $cachePath);
    $reader->registerDiscoverableBlocks(
        $sourceDirectory,
        'Capell\\BlockLibrary\\Tests\\Fixtures\\BuilderBlocks',
    );

    forceBuilderBlockDiscoveryCacheHit($reader);

    try {
        $reader->restoreCachedBlocks();

        expect($registry->allForTarget(BuilderBlockTarget::AdminFilament))->toBe([
            'hero' => HeroBuilderBlock::class,
            'legacy' => LegacyBuilderBlock::class,
        ]);
    } finally {
        $filesystem->delete($cachePath);
    }
});

it('clears stale legacy cache files and falls back to filesystem discovery', function (): void {
    $filesystem = new Filesystem;
    $cachePath = temporaryBuilderBlockCachePath('legacy');
    $registry = new BuilderBlockRegistry;
    $discovery = new BuilderBlockDiscovery($registry, $filesystem, $cachePath);

    $filesystem->put($cachePath, '<?php return ' . var_export([
        'hero' => HeroBuilderBlock::class,
    ], true) . ';');

    $discovery->registerDiscoverableBlocks(
        __DIR__ . '/../Fixtures/BuilderBlocks',
        'Capell\\BlockLibrary\\Tests\\Fixtures\\BuilderBlocks',
    );

    forceBuilderBlockDiscoveryCacheHit($discovery);

    try {
        expect($discovery->filamentBlocks())->toHaveCount(2)
            ->and($registry->allForTarget(BuilderBlockTarget::AdminFilament))->toBe([
                'hero' => HeroBuilderBlock::class,
                'legacy' => LegacyBuilderBlock::class,
            ])
            ->and($filesystem->exists($cachePath))->toBeFalse();
    } finally {
        $filesystem->delete($cachePath);
    }
});

it('invalidates cached builder blocks when discoverable files change', function (): void {
    $filesystem = new Filesystem;
    $cachePath = temporaryBuilderBlockCachePath('changed-files');
    $sourceDirectory = temporaryBuilderBlockDirectory('changed-files');
    $fixtureDirectory = __DIR__ . '/../Fixtures/BuilderBlocks';

    $filesystem->delete($cachePath);
    $filesystem->deleteDirectory($sourceDirectory);
    $filesystem->ensureDirectoryExists($sourceDirectory);
    $filesystem->copy($fixtureDirectory . '/HeroBuilderBlock.php', $sourceDirectory . '/HeroBuilderBlock.php');

    $writer = new BuilderBlockDiscovery(new BuilderBlockRegistry, $filesystem, $cachePath);
    $writer->registerDiscoverableBlocks(
        $sourceDirectory,
        'Capell\\BlockLibrary\\Tests\\Fixtures\\BuilderBlocks',
    );
    $writer->cacheBlocks();

    $filesystem->copy($fixtureDirectory . '/LegacyBuilderBlock.php', $sourceDirectory . '/LegacyBuilderBlock.php');

    $registry = new BuilderBlockRegistry;
    $reader = new BuilderBlockDiscovery($registry, $filesystem, $cachePath);
    $reader->registerDiscoverableBlocks(
        $sourceDirectory,
        'Capell\\BlockLibrary\\Tests\\Fixtures\\BuilderBlocks',
    );

    forceBuilderBlockDiscoveryCacheHit($reader);

    try {
        expect($reader->filamentBlocks())->toHaveCount(2)
            ->and($registry->allForTarget(BuilderBlockTarget::AdminFilament))->toBe([
                'hero' => HeroBuilderBlock::class,
                'legacy' => LegacyBuilderBlock::class,
            ])
            ->and($filesystem->exists($cachePath))->toBeFalse();
    } finally {
        $filesystem->delete($cachePath);
        $filesystem->deleteDirectory($sourceDirectory);
    }
});

it('rejects non builder block classes', function (): void {
    $registry = new BuilderBlockRegistry;
    $discovery = new BuilderBlockDiscovery($registry, new Filesystem);

    $invalidBlock = stdClass::class;

    $discovery->register($invalidBlock);
})->throws(InvalidArgumentException::class, 'must implement ' . FilamentBuilderBlock::class);
