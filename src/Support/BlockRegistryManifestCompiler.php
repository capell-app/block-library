<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Support;

use Capell\ContentBlocks\Contracts\BlockDemoContentProvider;
use Capell\ContentBlocks\Contracts\BlockFixtureProvider;
use Capell\ContentBlocks\Data\BlockDefinitionData;
use Capell\ContentBlocks\Data\BlockVariantData;
use Capell\ContentBlocks\Data\PublicBlockViewReference;
use Throwable;

final class BlockRegistryManifestCompiler
{
    /**
     * @param  callable(string): bool|null  $packageIsActive
     * @param  callable(string): bool|null  $viewExists
     */
    public function __construct(
        private readonly mixed $packageIsActive = null,
        private readonly mixed $viewExists = null,
    ) {}

    /**
     * @param  array<string, BlockDefinitionData>  $definitions
     * @return array{blocks: array<string, array<string, mixed>>, compiledAt: string}
     */
    public function compile(array $definitions): array
    {
        $blocks = [];

        foreach ($definitions as $definition) {
            $blocks[$definition->key] = [
                'key' => $definition->key,
                'category' => $definition->category,
                'sourcePackage' => $definition->sourcePackage,
                'publicView' => $definition->publicViewName(),
                'previewView' => $definition->previewViewName(),
                'fixtureProvider' => $definition->fixtureProvider,
                'demoContentProvider' => $definition->demoContentProvider,
                'defaultVariant' => $definition->defaultVariant->value(),
                'variants' => array_map(
                    static fn (BlockVariantData $variant): string => $variant->key->value(),
                    $definition->variants,
                ),
            ];
        }

        return [
            'blocks' => $blocks,
            'compiledAt' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, array<string, mixed>>
     */
    public function validBlocks(array $manifest): array
    {
        $blocks = $manifest['blocks'] ?? [];

        if (! is_array($blocks)) {
            return [];
        }

        return collect($blocks)
            ->filter(fn (mixed $entry): bool => is_array($entry) && $this->entryIsValid($entry))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function entryIsValid(array $entry): bool
    {
        $key = $entry['key'] ?? null;
        $sourcePackage = $entry['sourcePackage'] ?? null;
        $publicView = $entry['publicView'] ?? null;
        $previewView = $entry['previewView'] ?? null;

        foreach ([$key, $sourcePackage, $publicView, $previewView] as $value) {
            if (! is_string($value) || trim($value) === '') {
                return false;
            }
        }

        if (! $this->packageIsActive($sourcePackage)) {
            return false;
        }

        try {
            PublicBlockViewReference::from($publicView);
        } catch (Throwable) {
            return false;
        }

        if (! $this->classReferenceIsValid($entry['fixtureProvider'] ?? null, BlockFixtureProvider::class)) {
            return false;
        }

        if (! $this->classReferenceIsValid($entry['demoContentProvider'] ?? null, BlockDemoContentProvider::class)) {
            return false;
        }

        return $this->viewExists($publicView)
            && $this->viewExists($previewView);
    }

    /**
     * @param  class-string  $contract
     */
    private function classReferenceIsValid(mixed $class, string $contract): bool
    {
        return $class === null || (is_string($class) && is_a($class, $contract, true));
    }

    private function packageIsActive(string $package): bool
    {
        if ($package === 'unknown') {
            return true;
        }

        if (! is_callable($this->packageIsActive)) {
            return true;
        }

        try {
            return call_user_func($this->packageIsActive, $package);
        } catch (Throwable) {
            return false;
        }
    }

    private function viewExists(string $view): bool
    {
        if (! is_callable($this->viewExists)) {
            return true;
        }

        try {
            return call_user_func($this->viewExists, $view);
        } catch (Throwable) {
            return false;
        }
    }
}
