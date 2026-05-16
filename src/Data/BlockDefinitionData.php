<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Data;

use Capell\ContentBlocks\Contracts\BlockDemoContentProvider;
use Capell\ContentBlocks\Contracts\BlockFixtureProvider;
use Capell\ContentBlocks\Contracts\BlockRenderer;
use InvalidArgumentException;

final class BlockDefinitionData
{
    /**
     * @param  array<string, mixed>  $defaults
     * @param  class-string<BlockRenderer>|null  $renderer
     * @param  array<int, BlockVariantData>  $variants
     * @param  array<int, BlockSettingDefinitionData>  $settings
     * @param  array<string, mixed>  $defaultSettings
     * @param  class-string<BlockFixtureProvider>|null  $fixtureProvider
     * @param  class-string<BlockDemoContentProvider>|null  $demoContentProvider
     * @param  array<int, BlockScreenshotData>  $screenshots
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public string $category,
        public string $view,
        public array $defaults = [],
        public ?string $renderer = null,
        public bool $safeForPublicOutput = true,
        public string $sourcePackage = 'unknown',
        public array $variants = [],
        public ?BlockVariantKey $defaultVariant = null,
        public array $settings = [],
        public array $defaultSettings = [],
        public ?BlockContentContractData $contentContract = null,
        public ?BlockAccessibilityContractData $accessibilityContract = null,
        public ?PublicBlockViewReference $publicView = null,
        public ?AdminPreviewBlockViewReference $previewView = null,
        public ?string $fixtureProvider = null,
        public ?string $demoContentProvider = null,
        public array $screenshots = [],
        public ?BlockCompatibilityData $compatibility = null,
    ) {
        foreach ([
            'key' => $this->key,
            'label' => $this->label,
            'category' => $this->category,
            'view' => $this->view,
        ] as $field => $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException(sprintf('Block definition [%s] must not be empty.', $field));
            }
        }

        $this->variants = $this->variants === []
            ? [new BlockVariantData(BlockVariantKey::from('default'), 'capell-content-blocks::blocks.variants.default')]
            : $this->variants;

        $this->defaultVariant ??= $this->variants[0]->key;
        $this->contentContract ??= new BlockContentContractData;
        $this->accessibilityContract ??= new BlockAccessibilityContractData;
        $this->publicView ??= PublicBlockViewReference::from($this->view);
        $this->previewView ??= AdminPreviewBlockViewReference::from($this->view);
        $this->compatibility ??= new BlockCompatibilityData;

        $this->validateVariantDefaults();
        $this->validateProviderContracts();
    }

    public function publicViewName(): string
    {
        return $this->publicView->value();
    }

    public function previewViewName(): string
    {
        return $this->previewView->value();
    }

    /**
     * @return array<int, string>
     */
    public function variantKeys(): array
    {
        return array_map(
            static fn (BlockVariantData $variant): string => $variant->key->value(),
            $this->variants,
        );
    }

    public function supportsVariant(string $variant): bool
    {
        return in_array($variant, $this->variantKeys(), true);
    }

    private function validateVariantDefaults(): void
    {
        if (! $this->supportsVariant($this->defaultVariant->value())) {
            throw new InvalidArgumentException(sprintf(
                'Default block variant [%s] is not registered for block [%s].',
                $this->defaultVariant->value(),
                $this->key,
            ));
        }
    }

    private function validateProviderContracts(): void
    {
        if ($this->fixtureProvider !== null && ! is_a($this->fixtureProvider, BlockFixtureProvider::class, true)) {
            throw new InvalidArgumentException(sprintf('Block fixture provider [%s] must implement %s.', $this->fixtureProvider, BlockFixtureProvider::class));
        }

        if ($this->demoContentProvider !== null && ! is_a($this->demoContentProvider, BlockDemoContentProvider::class, true)) {
            throw new InvalidArgumentException(sprintf('Block demo content provider [%s] must implement %s.', $this->demoContentProvider, BlockDemoContentProvider::class));
        }
    }
}
