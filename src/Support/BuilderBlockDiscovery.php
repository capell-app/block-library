<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Support;

use Capell\ContentBlocks\Contracts\FilamentBuilderBlock;
use Capell\ContentBlocks\Enums\BuilderBlockTarget;
use Filament\Forms\Components\Builder\Block;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use ReflectionClass;

final class BuilderBlockDiscovery
{
    /** @var array<string, string> */
    private array $blockDiscoveryPaths = [];

    /** @var array<string, class-string>|null */
    private ?array $discoveredBlocks = null;

    private ?bool $hasCachedBlocks = null;

    public function __construct(
        private readonly BuilderBlockRegistry $registry,
        private readonly Filesystem $filesystem,
        private readonly ?string $cachePath = null,
    ) {}

    /**
     * @param  class-string  $blockClass
     */
    public function register(string $blockClass): self
    {
        if (! $this->isBuilderBlockClass($blockClass)) {
            throw new InvalidArgumentException(
                sprintf('Builder block class `%s` must implement %s or expose legacy getBlockName() and make() methods.', $blockClass, FilamentBuilderBlock::class),
            );
        }

        $this->registry->register(
            $this->blockNameFor($blockClass),
            BuilderBlockTarget::AdminFilament,
            $blockClass,
        );

        return $this;
    }

    public function registerDiscoverableBlocks(string $directory, string $namespace): self
    {
        $this->blockDiscoveryPaths[$directory] = trim($namespace, '\\');
        $this->discoveredBlocks = null;

        return $this;
    }

    /**
     * @return list<Block>
     */
    public function filamentBlocks(): array
    {
        $this->ensureBlocksDiscovered();

        $blocks = [];

        foreach ($this->registry->allForTarget(BuilderBlockTarget::AdminFilament) as $blockClass) {
            if (is_string($blockClass) && $this->isBuilderBlockClass($blockClass)) {
                $blocks[] = $blockClass::make();
            }
        }

        return $blocks;
    }

    public function hasCachedBlocks(): bool
    {
        return $this->hasCachedBlocks ??= (! app()->runningInConsole()) && $this->filesystem->exists($this->getBlockCachePath());
    }

    public function cacheBlocks(): void
    {
        $this->hasCachedBlocks = false;
        $this->discoveredBlocks = null;

        $this->ensureBlocksDiscovered();

        $cachePath = $this->getBlockCachePath();

        $this->filesystem->ensureDirectoryExists((string) str($cachePath)->beforeLast(DIRECTORY_SEPARATOR));

        $this->filesystem->put(
            $cachePath,
            '<?php return ' . var_export($this->discoveredBlocks ?? [], true) . ';',
        );

        $this->hasCachedBlocks = true;
    }

    public function restoreCachedBlocks(): void
    {
        if (! $this->hasCachedBlocks()) {
            return;
        }

        /** @var array<string, class-string> $cached */
        $cached = require $this->getBlockCachePath();

        $this->discoveredBlocks = $cached;

        foreach ($cached as $blockName => $blockClass) {
            $this->registry->register($blockName, BuilderBlockTarget::AdminFilament, $blockClass);
        }
    }

    public function clearCachedBlocks(): void
    {
        $this->filesystem->delete($this->getBlockCachePath());

        $this->hasCachedBlocks = false;
        $this->discoveredBlocks = null;
    }

    public function getBlockCachePath(): string
    {
        return $this->cachePath
            ?? config('filament.cache_path', base_path('bootstrap/cache/filament')) . DIRECTORY_SEPARATOR . 'builder-blocks.php';
    }

    private function ensureBlocksDiscovered(): void
    {
        if ($this->discoveredBlocks !== null) {
            return;
        }

        if ($this->hasCachedBlocks()) {
            $this->restoreCachedBlocks();

            return;
        }

        $this->discoveredBlocks = [];

        foreach ($this->blockDiscoveryPaths as $directory => $namespace) {
            $this->discoverBlockFiles($directory, $namespace);
        }
    }

    private function discoverBlockFiles(string $directory, string $namespace): void
    {
        if (! $this->filesystem->exists($directory)) {
            return;
        }

        foreach ($this->filesystem->allFiles($directory) as $file) {
            $blockClass = (string) str($namespace)
                ->append('\\', $file->getRelativePathname())
                ->replace([DIRECTORY_SEPARATOR, '.php'], ['\\', '']);

            if (! class_exists($blockClass)) {
                continue;
            }

            $reflection = new ReflectionClass($blockClass);

            if ($reflection->isAbstract()) {
                continue;
            }

            if (! $this->isBuilderBlockClass($blockClass)) {
                continue;
            }

            /** @var class-string $blockClass */
            $this->register($blockClass);

            $this->discoveredBlocks[$this->blockNameFor($blockClass)] = $blockClass;
        }
    }

    /**
     * @param  class-string  $blockClass
     */
    private function isBuilderBlockClass(string $blockClass): bool
    {
        if (is_a($blockClass, FilamentBuilderBlock::class, true)) {
            return true;
        }

        return method_exists($blockClass, 'getBlockName') && method_exists($blockClass, 'make');
    }

    /**
     * @param  class-string  $blockClass
     */
    private function blockNameFor(string $blockClass): string
    {
        if (is_a($blockClass, FilamentBuilderBlock::class, true)) {
            return $blockClass::getBuilderBlockName();
        }

        /** @var callable(): string $legacyNameResolver */
        $legacyNameResolver = [$blockClass, 'getBlockName'];

        return $legacyNameResolver();
    }
}
