<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Data;

use Capell\BlockLibrary\Contracts\BlockDemoContentProvider;
use Capell\BlockLibrary\Contracts\BlockFixtureProvider;
use Capell\BlockLibrary\Contracts\BlockRenderer;
use InvalidArgumentException;

final class BlockDefinitionData
{
    /** @var array<int, BlockVariantData> */
    public array $variants;

    public BlockVariantKey $defaultVariant;

    public BlockContentContractData $contentContract;

    public BlockAccessibilityContractData $accessibilityContract;

    public PublicBlockViewReference $publicView;

    public AdminPreviewBlockViewReference $previewView;

    public BlockCompatibilityData $compatibility;

    /**
     * @param  array<string, mixed>  $defaults
     * @param  class-string<BlockRenderer>|null  $renderer
     * @param  array<int, BlockVariantData>  $variants
     * @param  array<int, BlockSettingDefinitionData>  $settings
     * @param  array<string, mixed>  $defaultSettings
     * @param  class-string|null  $fixtureProvider
     * @param  class-string|null  $demoContentProvider
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
        array $variants = [],
        ?BlockVariantKey $defaultVariant = null,
        public array $settings = [],
        public array $defaultSettings = [],
        ?BlockContentContractData $contentContract = null,
        ?BlockAccessibilityContractData $accessibilityContract = null,
        ?PublicBlockViewReference $publicView = null,
        ?AdminPreviewBlockViewReference $previewView = null,
        public ?string $fixtureProvider = null,
        public ?string $demoContentProvider = null,
        public array $screenshots = [],
        ?BlockCompatibilityData $compatibility = null,
        public int $schemaVersion = 1,
        public bool $deprecated = false,
        public ?string $replacementKey = null,
        public ?string $deprecationNote = null,
    ) {
        $this->variants = $variants === []
            ? [new BlockVariantData(BlockVariantKey::from('default'), 'capell-block-library::blocks.variants.default')]
            : $variants;
        $this->defaultVariant = $defaultVariant ?? $this->variants[0]->key;
        $this->contentContract = $contentContract ?? new BlockContentContractData;
        $this->accessibilityContract = $accessibilityContract ?? new BlockAccessibilityContractData;
        $this->publicView = $publicView ?? PublicBlockViewReference::from($this->view);
        $this->previewView = $previewView ?? AdminPreviewBlockViewReference::from($this->view);
        $this->compatibility = $compatibility ?? new BlockCompatibilityData;

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

        $this->validateVariantDefaults();
        $this->validateProviderContracts();
        $this->validateLifecycleMetadata();
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

    private function validateLifecycleMetadata(): void
    {
        if ($this->schemaVersion < 1) {
            throw new InvalidArgumentException(sprintf('Block definition [%s] schema version must be at least 1.', $this->key));
        }

        if ($this->replacementKey !== null && trim($this->replacementKey) === '') {
            throw new InvalidArgumentException(sprintf('Block definition [%s] replacement key cannot be empty.', $this->key));
        }

        if ($this->replacementKey === $this->key) {
            throw new InvalidArgumentException(sprintf('Block definition [%s] cannot replace itself.', $this->key));
        }

        if ($this->deprecated && ($this->deprecationNote === null || trim($this->deprecationNote) === '')) {
            throw new InvalidArgumentException(sprintf('Deprecated block definition [%s] must include a deprecation note.', $this->key));
        }
    }
}
