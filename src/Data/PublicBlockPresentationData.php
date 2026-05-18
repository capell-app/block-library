<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Data;

final class PublicBlockPresentationData
{
    public function __construct(
        public readonly string $variant = 'default',
        public readonly string $spacing = 'normal',
        public readonly string $background = 'default',
        public readonly string $mediaPosition = 'top',
        public readonly int $cardsPerRow = 3,
        public readonly bool $showCta = true,
        public readonly string $headingWidth = 'normal',
        public readonly ?string $anchorId = null,
    ) {}

    /**
     * @return array{
     *     variant: string,
     *     spacing: string,
     *     background: string,
     *     mediaPosition: string,
     *     cardsPerRow: int,
     *     showCta: bool,
     *     headingWidth: string,
     *     anchorId: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'variant' => $this->variant,
            'spacing' => $this->spacing,
            'background' => $this->background,
            'mediaPosition' => $this->mediaPosition,
            'cardsPerRow' => $this->cardsPerRow,
            'showCta' => $this->showCta,
            'headingWidth' => $this->headingWidth,
            'anchorId' => $this->anchorId,
        ];
    }
}
